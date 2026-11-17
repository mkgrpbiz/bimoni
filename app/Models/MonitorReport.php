<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorReport extends Model
{
    protected $fillable = [
        'application_id', 'user_id', 'campaign_id', 'referral_fee',
        'report_body', 'purchase_type', 'continuation_round', 'purchase_amount', 'bonus_amount', 'adjustment_amount', 'adjustment_reason', 'payment_method', 'payment_method_other',
        'status', 'reviewed_by', 'reviewed_at', 'reject_reason',
        'payment_status', 'paid_at', 'zengin_batch',
        'report_image',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'paid_at'     => 'datetime',
        ];
    }

    public function application() { return $this->belongsTo(Application::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function campaign()    { return $this->belongsTo(Campaign::class); }
    public function images()      { return $this->hasMany(MonitorReportImage::class)->orderBy('sort_order'); }
    public function reviewedBy()  { return $this->belongsTo(Admin::class, 'reviewed_by'); }

    // 協力金 = コース別設定（CampaignCourse.cooperation_fee/continuation_cooperation_fee）が
    // あれば優先、無ければ案件共通のcooperation_fee/continuation_cooperation_feeを使う。
    // 以前はこの判定（購入タイプ別の協力金分岐）が10箇所以上のコントローラ・Blade・Serviceに
    // 個別にコピペされていたため、ここに一本化する（打診モーダルの重複実装と同種の事故を防ぐ）
    public function cooperationFee(): int
    {
        $course = $this->application?->course;

        if ($this->purchase_type === 'continuation') {
            if ($course && $course->continuation_cooperation_fee !== null) {
                return (int) $course->continuation_cooperation_fee;
            }
            return (int) ($this->campaign?->continuation_cooperation_fee ?? 0);
        }

        if ($course && $course->cooperation_fee !== null) {
            return (int) $course->cooperation_fee;
        }
        return (int) ($this->campaign?->cooperation_fee ?? 0);
    }

    // 報告1件あたりの実際の支払い額 = モニター経費 + 協力金 + キャンペーンボーナス + 金額修正
    public function totalPayout(): int
    {
        return ($this->purchase_amount ?? 0) + $this->cooperationFee() + ($this->bonus_amount ?? 0) + ($this->adjustment_amount ?? 0);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'  => '承認待ち',
            'approved' => '承認',
            'rejected' => '差戻し',
            default    => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'  => 'bg-yellow-500 text-white',
            'approved' => 'bg-green-500 text-white',
            'rejected' => 'bg-red-500 text-white',
            default    => 'bg-gray-500 text-white',
        };
    }
}
