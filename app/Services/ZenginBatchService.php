<?php

namespace App\Services;

use App\Models\CollectionReport;
use App\Models\MonitorReport;
use App\Models\UserReferralReward;
use App\Support\ZenginRecipient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 住信SBIネット銀行の一括振込サービスが1日300万円までのため、
 * 同じ振込先（銀行口座）はまとめつつ、300万円を超えないように①②③…とバッチ分割する。
 * バッチ番号は一度確定すると変えない（すでに全銀出力・予約済み/支払済みにしたバッチの中身を後から
 * 書き換えると、提出済みのCSVと実際の処理内容がズレてしまうため）。新しく承認された分は
 * 既存バッチが未処理（pending）のままなら同じ振込先の既存バッチに追加し、それ以外は新しいバッチに回す。
 */
class ZenginBatchService
{
    public const LIMIT = 3_000_000;

    public function summarize(Carbon $start, Carbon $end): Collection
    {
        $this->ensureBatches($start, $end);

        $batches = [];

        foreach ($this->monitorsInRange($start, $end)->whereNotNull('zengin_batch') as $r) {
            $n = $r->zengin_batch;
            $batches[$n] ??= ['total' => 0, 'count' => 0, 'statuses' => []];
            $batches[$n]['total'] += $this->monitorAmount($r);
            $batches[$n]['count']++;
            $batches[$n]['statuses'][] = $r->payment_status;
        }

        foreach ($this->collectionsInRange($start, $end)->whereNotNull('zengin_batch') as $r) {
            $n = $r->zengin_batch;
            $batches[$n] ??= ['total' => 0, 'count' => 0, 'statuses' => []];
            $batches[$n]['total'] += $r->totalFee();
            $batches[$n]['count']++;
            $batches[$n]['statuses'][] = $r->payment_status;
        }

        foreach ($this->referralsInRange($start, $end)->whereNotNull('zengin_batch') as $rr) {
            $n = $rr->zengin_batch;
            $batches[$n] ??= ['total' => 0, 'count' => 0, 'statuses' => []];
            $batches[$n]['total'] += $rr->amount;
            $batches[$n]['count']++;
            $batches[$n]['statuses'][] = $rr->payment_status;
        }

        ksort($batches);

        return collect($batches)->map(function ($b, $n) {
            $hasPending = in_array('pending', $b['statuses'], true);
            $allPaid    = $b['count'] > 0 && count(array_diff($b['statuses'], ['paid'])) === 0;

            return [
                'batch'      => $n,
                'label'      => self::circled((int) $n),
                'total'      => $b['total'],
                'count'      => $b['count'],
                'hasPending' => $hasPending,
                'allPaid'    => $allPaid,
                'overLimit'  => $b['total'] > self::LIMIT,
            ];
        })->values();
    }

    /** ユーザーIDごとに、そのユーザーの行が属するバッチ番号の一覧（ラベル付き）を返す */
    public function batchLabelsByUser(Carbon $start, Carbon $end): array
    {
        $map = [];

        $add = function (?int $userId, ?int $batch) use (&$map) {
            if (!$userId || !$batch) {
                return;
            }
            $map[$userId] ??= [];
            if (!in_array($batch, $map[$userId], true)) {
                $map[$userId][] = $batch;
            }
        };

        foreach ($this->monitorsInRange($start, $end) as $r) {
            $add($r->user_id, $r->zengin_batch);
        }
        foreach ($this->collectionsInRange($start, $end) as $r) {
            $add($r->user_id, $r->zengin_batch);
        }
        foreach ($this->referralsInRange($start, $end) as $rr) {
            $add($rr->referrer_user_id, $rr->zengin_batch);
        }

        foreach ($map as $uid => $batches) {
            sort($batches);
            $map[$uid] = implode(',', array_map(fn($n) => self::circled($n), $batches));
        }

        return $map;
    }

    public function ensureBatches(Carbon $start, Carbon $end): void
    {
        $monitors    = $this->monitorsInRange($start, $end);
        $collections = $this->collectionsInRange($start, $end);
        $referrals   = $this->referralsInRange($start, $end);

        $maxBatch = max(
            (int) $monitors->max('zengin_batch'),
            (int) $collections->max('zengin_batch'),
            (int) $referrals->max('zengin_batch'),
        );

        // Eloquent Collectionのmerge()はモデルの主キーでキー付けされるため、
        // MonitorReport/CollectionReport/UserReferralRewardのように型が異なるコレクションを
        // merge()すると主キーの衝突で項目が消える。必ず個別にループすること。
        $sealedBatches = [];
        foreach ([$monitors, $collections, $referrals] as $coll) {
            foreach ($coll as $item) {
                if ($item->zengin_batch && $item->payment_status !== 'pending') {
                    $sealedBatches[$item->zengin_batch] = true;
                }
            }
        }

        $existingBatchByKey = [];
        foreach ($monitors as $r) {
            if ($r->zengin_batch && ($key = ZenginRecipient::key($r->user))) {
                $existingBatchByKey[$key] ??= $r->zengin_batch;
            }
        }
        foreach ($collections as $r) {
            if ($r->zengin_batch && ($key = ZenginRecipient::key($r->user))) {
                $existingBatchByKey[$key] ??= $r->zengin_batch;
            }
        }
        foreach ($referrals as $rr) {
            if ($rr->zengin_batch && ($key = ZenginRecipient::key($rr->referrer))) {
                $existingBatchByKey[$key] ??= $rr->zengin_batch;
            }
        }

        // 未割当分を振込先単位に集約
        $unassigned = []; // key => ['amount'=>int, 'monitorIds'=>[], 'collectionIds'=>[], 'referralIds'=>[]]
        $add = function (?string $key, string $type, int $id, int $amount) use (&$unassigned) {
            if ($key === null) {
                return;
            }
            $unassigned[$key] ??= ['amount' => 0, 'monitorIds' => [], 'collectionIds' => [], 'referralIds' => []];
            $unassigned[$key]['amount'] += $amount;
            $unassigned[$key][$type . 'Ids'][] = $id;
        };

        foreach ($monitors->whereNull('zengin_batch') as $r) {
            $add(ZenginRecipient::key($r->user), 'monitor', $r->id, $this->monitorAmount($r));
        }
        foreach ($collections->whereNull('zengin_batch') as $r) {
            $add(ZenginRecipient::key($r->user), 'collection', $r->id, $r->totalFee());
        }
        foreach ($referrals->whereNull('zengin_batch') as $rr) {
            $add(ZenginRecipient::key($rr->referrer), 'referral', $rr->id, $rr->amount);
        }

        if (empty($unassigned)) {
            return;
        }

        // 既存バッチがまだ未処理(pending)のまま残っている振込先は、そのバッチに合流させる
        $toPack = [];
        $assignedBatch = [];
        foreach ($unassigned as $key => $data) {
            $existing = $existingBatchByKey[$key] ?? null;
            if ($existing !== null && empty($sealedBatches[$existing])) {
                $assignedBatch[$key] = $existing;
            } else {
                $toPack[$key] = $data['amount'];
            }
        }

        // 新規振込先はFirst-Fit-Decreasingで300万円以内のバッチに詰める
        arsort($toPack);
        $bins = [];
        foreach ($toPack as $key => $amount) {
            $placed = false;
            foreach ($bins as $i => $bin) {
                if ($bin['total'] + $amount <= self::LIMIT) {
                    $bins[$i]['total'] += $amount;
                    $bins[$i]['keys'][] = $key;
                    $placed = true;
                    break;
                }
            }
            if (!$placed) {
                $bins[] = ['total' => $amount, 'keys' => [$key]];
            }
        }

        $n = $maxBatch;
        foreach ($bins as $bin) {
            $n++;
            foreach ($bin['keys'] as $key) {
                $assignedBatch[$key] = $n;
            }
        }

        // 確定した割り当てをDBへ反映
        $monitorUpdates    = [];
        $collectionUpdates = [];
        $referralUpdates   = [];
        foreach ($unassigned as $key => $data) {
            $batch = $assignedBatch[$key];
            foreach ($data['monitorIds'] as $id) {
                $monitorUpdates[$batch][] = $id;
            }
            foreach ($data['collectionIds'] as $id) {
                $collectionUpdates[$batch][] = $id;
            }
            foreach ($data['referralIds'] as $id) {
                $referralUpdates[$batch][] = $id;
            }
        }

        foreach ($monitorUpdates as $batch => $ids) {
            MonitorReport::whereIn('id', $ids)->update(['zengin_batch' => $batch]);
        }
        foreach ($collectionUpdates as $batch => $ids) {
            CollectionReport::whereIn('id', $ids)->update(['zengin_batch' => $batch]);
        }
        foreach ($referralUpdates as $batch => $ids) {
            UserReferralReward::whereIn('id', $ids)->update(['zengin_batch' => $batch]);
        }
    }

    private function monitorsInRange(Carbon $start, Carbon $end): Collection
    {
        return MonitorReport::with(['user', 'campaign'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    private function collectionsInRange(Carbon $start, Carbon $end): Collection
    {
        return CollectionReport::with('user')
            ->where('status', 'approved')
            ->whereRaw(CollectionReport::monthCutoffSql() . ' BETWEEN ? AND ?', [$start, $end])
            ->get();
    }

    private function referralsInRange(Carbon $start, Carbon $end): Collection
    {
        return UserReferralReward::with('referrer')
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    private function monitorAmount(MonitorReport $r): int
    {
        $coopFee = $r->purchase_type === 'continuation'
            ? ($r->campaign?->continuation_cooperation_fee ?? 0)
            : ($r->campaign?->cooperation_fee ?? 0);
        return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0) + ($r->adjustment_amount ?? 0);
    }

    public static function circled(int $n): string
    {
        if ($n >= 1 && $n <= 20) {
            return mb_chr(0x2460 + $n - 1);
        }
        if ($n >= 21 && $n <= 35) {
            return mb_chr(0x3251 + $n - 21);
        }
        if ($n >= 36 && $n <= 50) {
            return mb_chr(0x32B1 + $n - 36);
        }
        return "($n)";
    }
}
