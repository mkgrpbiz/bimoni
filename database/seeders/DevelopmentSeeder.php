<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\MonitorReport;
use App\Models\Point;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::first();

        // ===== カテゴリ =====
        $catNames = ['フェイシャルケア', 'ボディケア', 'ヘアケア', 'ネイル', 'まつ毛', '脱毛', 'マッサージ', 'ホワイトニング'];
        $categories = [];
        foreach ($catNames as $name) {
            $categories[] = Category::firstOrCreate(['name' => $name]);
        }

        // ===== 案件 10件 =====
        $campaignData = [
            [
                'title' => 'スキンケアフェイシャルエステ体験モニター',
                'campaign_type' => 'experience',
                'description' => '最新のフェイシャルエステを体験していただきます。施術後はSNSへの投稿をお願いします。毛穴ケア・保湿を中心に、あなたの肌悩みに合わせた施術を行います。',
                'requirements' => '20〜40代女性、インスタグラムのフォロワー300名以上',
                'notes' => 'アレルギーのある方はご応募をお控えください。施術当日はメイクをせずにお越しください。',
                'product_name' => 'プレミアムフェイシャルエステ（60分）', 'product_price' => 12000, 'cooperation_fee' => 5000,
                'capacity' => 5, 'category_id' => $categories[0]->id,
            ],
            [
                'title' => 'ヘアカラートリートメント自宅体験モニター',
                'campaign_type' => 'product',
                'description' => '自宅で使えるヘアカラートリートメントのモニターです。1ヶ月間使用していただき、使用前後の写真と詳細なレビューをご提出ください。',
                'requirements' => '白髪が気になる30〜60代の方、週2回以上の使用が可能な方',
                'notes' => '商品のお届けまで1週間程度かかります。返品・返却は不要です。',
                'product_name' => 'カラートリートメントEX（200g）', 'product_price' => 4500, 'cooperation_fee' => 3000,
                'capacity' => 8, 'category_id' => $categories[2]->id,
            ],
            [
                'title' => 'まつ毛エクステ施術モニター',
                'campaign_type' => 'experience',
                'description' => 'まつ毛エクステの施術を無料で体験いただけます。ビフォーアフターの写真撮影にご協力ください。',
                'requirements' => '18〜35歳女性、まつ毛エクステ未経験の方優遇',
                'notes' => 'コンタクトレンズ着用の方は外していただく必要があります。施術時間は約90分です。',
                'product_name' => 'まつ毛エクステ（100本）', 'product_price' => 8000, 'cooperation_fee' => 4000,
                'capacity' => 4, 'category_id' => $categories[4]->id,
            ],
            [
                'title' => '脱毛サロン体験モニター（初回2部位）',
                'campaign_type' => 'experience',
                'description' => '話題の脱毛サロンで初回体験をしていただきます。口コミ・レビューの投稿をお願いします。痛みの少ない最新機器を使用しています。',
                'requirements' => '18歳以上の女性、妊娠中・授乳中の方は不可',
                'notes' => '施術当日は日焼け止めをご持参ください。生理中は施術をお断りする場合があります。',
                'product_name' => '脱毛初回体験（2部位・60分）', 'product_price' => 20000, 'cooperation_fee' => 6000,
                'capacity' => 6, 'category_id' => $categories[5]->id,
            ],
            [
                'title' => 'ネイルアートモニター（ジェルネイル）',
                'campaign_type' => 'experience',
                'description' => 'ジェルネイルの施術を体験していただきます。季節に合わせたデザインをサロンとご相談の上決定します。',
                'requirements' => '20代〜40代女性、爪が一定の長さ以上ある方',
                'notes' => '施術時間は約2時間です。オフ込みの場合は追加30分かかります。',
                'product_name' => 'ジェルネイル（フルカラー）', 'product_price' => 8500, 'cooperation_fee' => 2500,
                'capacity' => 10, 'category_id' => $categories[3]->id,
            ],
            [
                'title' => 'リンパマッサージ全身体験モニター',
                'campaign_type' => 'experience',
                'description' => 'リンパマッサージの全身コース（60分）を体験していただきます。むくみ・疲労回復に効果的です。',
                'requirements' => '20〜50代女性',
                'notes' => '施術前後に写真撮影があります。妊娠中の方はご遠慮ください。',
                'product_name' => 'リンパマッサージ全身コース（60分）', 'product_price' => 9500, 'cooperation_fee' => 5500,
                'capacity' => 4, 'category_id' => $categories[6]->id,
            ],
            [
                'title' => '美容液サンプルモニター（1ヶ月間）',
                'campaign_type' => 'product',
                'description' => '新発売の高濃度ビタミンC美容液を1ヶ月間お試しいただきます。使用前・使用後の写真と詳細なレビューをご提出ください。',
                'requirements' => '30〜50代女性、乾燥肌・混合肌の方',
                'notes' => '商品は郵送でお届けします。他の美容液との併用はお控えください。',
                'product_name' => 'プレミアム美容液（30mL）', 'product_price' => 6800, 'cooperation_fee' => 2000,
                'capacity' => 15, 'category_id' => $categories[0]->id,
            ],
            [
                'title' => 'ホワイトニングサロン体験モニター',
                'campaign_type' => 'experience',
                'description' => 'セルフホワイトニングを体験していただきます。施術前後の歯の色の変化を専用チャートで記録してください。',
                'requirements' => '18歳以上の方、歯の治療中・知覚過敏の方は不可',
                'notes' => '施術は約30分です。施術当日はコーヒー・紅茶はお控えください。',
                'product_name' => 'セルフホワイトニング（1回）', 'product_price' => 5500, 'cooperation_fee' => 8000,
                'capacity' => 8, 'category_id' => $categories[7]->id,
            ],
            [
                'title' => 'ヘッドスパ体験モニター（頭皮ケア・45分）',
                'campaign_type' => 'experience',
                'description' => 'ヘッドスパの施術（45分）を体験していただきます。頭皮状態の改善・リラクゼーション効果を実感してください。',
                'requirements' => '20〜50代男女、頭皮の乾燥・フケ・抜け毛が気になる方',
                'notes' => '来店は予約制です。パーマ・カラー直後の方はご遠慮ください。',
                'product_name' => 'プレミアムヘッドスパ（45分）', 'product_price' => 7000, 'cooperation_fee' => 4500,
                'capacity' => 6, 'category_id' => $categories[2]->id,
            ],
            [
                'title' => '痩身エステ体験モニター（お腹・太もも）',
                'campaign_type' => 'recovery',
                'description' => '最新機器を使用した痩身エステをお腹と太もも部位で体験していただきます。施術前後のサイズ測定にもご協力ください。',
                'requirements' => '20〜50代女性、BMI18.5〜30の方',
                'notes' => '施術後は激しい運動をお控えください。月経中は施術をお断りする場合があります。',
                'product_name' => '痩身エステ（2部位・60分）', 'product_price' => 15000, 'cooperation_fee' => 7000,
                'capacity' => 4, 'category_id' => $categories[1]->id,
            ],
        ];

        $campaigns = [];
        foreach ($campaignData as $data) {
            $campaigns[] = Campaign::create(array_merge($data, [
                'status' => 'published',
                'created_by' => $admin?->id,
                'application_start_at' => now()->subMonths(2)->toDateString(),
                'application_end_at' => now()->addMonths(1)->toDateString(),
            ]));
        }

        // ===== モニター会員 20件 =====
        $userData = [
            ['name' => '田中 さくら',   'name_kana' => 'タナカ サクラ',    'gender' => 'female', 'area' => '東京都',   'birthdate' => '1995-03-15'],
            ['name' => '山田 あかね',   'name_kana' => 'ヤマダ アカネ',    'gender' => 'female', 'area' => '神奈川県', 'birthdate' => '1990-07-22'],
            ['name' => '佐藤 みなみ',   'name_kana' => 'サトウ ミナミ',    'gender' => 'female', 'area' => '大阪府',   'birthdate' => '1998-11-08'],
            ['name' => '伊藤 はるか',   'name_kana' => 'イトウ ハルカ',    'gender' => 'female', 'area' => '埼玉県',   'birthdate' => '1993-05-30'],
            ['name' => '渡辺 ゆき',     'name_kana' => 'ワタナベ ユキ',    'gender' => 'female', 'area' => '千葉県',   'birthdate' => '1988-12-01'],
            ['name' => '中村 のぞみ',   'name_kana' => 'ナカムラ ノゾミ',  'gender' => 'female', 'area' => '東京都',   'birthdate' => '2000-04-18'],
            ['name' => '小林 まりな',   'name_kana' => 'コバヤシ マリナ',  'gender' => 'female', 'area' => '愛知県',   'birthdate' => '1996-08-25'],
            ['name' => '加藤 えみ',     'name_kana' => 'カトウ エミ',      'gender' => 'female', 'area' => '福岡県',   'birthdate' => '1992-02-14'],
            ['name' => '吉田 りな',     'name_kana' => 'ヨシダ リナ',      'gender' => 'female', 'area' => '東京都',   'birthdate' => '1997-06-03'],
            ['name' => '山口 あおい',   'name_kana' => 'ヤマグチ アオイ',  'gender' => 'female', 'area' => '兵庫県',   'birthdate' => '1991-09-17'],
            ['name' => '鈴木 たかし',   'name_kana' => 'スズキ タカシ',    'gender' => 'male',   'area' => '東京都',   'birthdate' => '1985-01-28'],
            ['name' => '高橋 けんじ',   'name_kana' => 'タカハシ ケンジ',  'gender' => 'male',   'area' => '大阪府',   'birthdate' => '1980-10-05'],
            ['name' => '松本 ひろき',   'name_kana' => 'マツモト ヒロキ',  'gender' => 'male',   'area' => '神奈川県', 'birthdate' => '1994-03-22'],
            ['name' => '井上 だいすけ', 'name_kana' => 'イノウエ ダイスケ','gender' => 'male',   'area' => '埼玉県',   'birthdate' => '1989-07-11'],
            ['name' => '木村 しょうた', 'name_kana' => 'キムラ ショウタ',  'gender' => 'male',   'area' => '東京都',   'birthdate' => '2001-05-07'],
            ['name' => '林 りょうた',   'name_kana' => 'ハヤシ リョウタ',  'gender' => 'male',   'area' => '京都府',   'birthdate' => '1987-11-30'],
            ['name' => '清水 まさや',   'name_kana' => 'シミズ マサヤ',    'gender' => 'male',   'area' => '千葉県',   'birthdate' => '1993-08-16'],
            ['name' => '池田 けいた',   'name_kana' => 'イケダ ケイタ',    'gender' => 'male',   'area' => '愛知県',   'birthdate' => '1999-04-02'],
            ['name' => '橋本 こうじ',   'name_kana' => 'ハシモト コウジ',  'gender' => 'male',   'area' => '福岡県',   'birthdate' => '1984-12-19'],
            ['name' => '山本 ゆうき',   'name_kana' => 'ヤマモト ユウキ',  'gender' => 'male',   'area' => '東京都',   'birthdate' => '1996-06-28'],
        ];

        $timeSets = [
            ['平日午前', '土日午後'], ['平日午後', '土日午前'], ['土日午前', '土日午後'],
            ['平日夜間', '土日夜間'], ['平日午前', '平日午後'],
        ];

        $users = [];
        foreach ($userData as $i => $data) {
            $users[] = User::create(array_merge($data, [
                'line_user_id'         => 'DEV_' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'available_times'      => $timeSets[$i % 5],
                'wants_continuation'   => ($i % 3 === 0) ? 0 : 1,
                'point_balance'        => 0,
                'status'               => 'active',
                'imported_from'        => 'new',
                'profile_completed_at' => now()->subDays(rand(30, 90)),
                'phone'                => '090-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'email'                => 'monitor' . ($i + 1) . '@example.com',
            ]));
        }

        // ===== 応募 30件 =====
        // (user_index, campaign_index) — 全てユニーク
        $pairs = [
            [0,0],[0,1],  [1,2],[1,3],  [2,4],[2,5],  [3,6],[3,7],  [4,8],[4,9],
            [5,0],[5,3],  [6,1],[6,4],  [7,2],[7,6],  [8,5],[8,8],  [9,7],[9,9],
            [10,0],[11,1],[12,2],[13,3],[14,4],[15,5],[16,6],[17,7],[18,8],[19,9],
        ];

        // ステータス配分: pending×8 / selected×1 / scheduled×4 / completed×1
        //                 reported×5 / approved×5 / point_granted×3 / rejected×3
        $statusMap = array_merge(
            array_fill(0, 8, 'pending'),
            ['selected'],
            array_fill(9, 4, 'scheduled'),
            ['completed'],
            array_fill(14, 5, 'reported'),
            array_fill(19, 5, 'approved'),
            array_fill(24, 3, 'point_granted'),
            array_fill(27, 3, 'rejected')
        );

        $applications = [];
        foreach ($pairs as $i => [$ui, $ci]) {
            $status    = $statusMap[$i];
            $appliedAt = Carbon::now()->subDays(rand(45, 90));

            $data = [
                'user_id'       => $users[$ui]->id,
                'campaign_id'   => $campaigns[$ci]->id,
                'status'        => $status,
                'applied_at'    => $appliedAt,
                'imported_from' => 'new',
            ];

            if (in_array($status, ['selected','scheduled','completed','reported','approved','point_granted','rejected'])) {
                $data['selected_at'] = $appliedAt->copy()->addDays(rand(3, 7));
            }
            if (in_array($status, ['scheduled','completed','reported','approved','point_granted'])) {
                $data['schedule_confirmed_at'] = $appliedAt->copy()->addDays(rand(10, 18));
            }
            if (in_array($status, ['completed','reported','approved','point_granted'])) {
                $data['completed_at'] = $appliedAt->copy()->addDays(rand(20, 30));
            }
            if (in_array($status, ['reported','approved','point_granted'])) {
                $data['reported_at'] = $appliedAt->copy()->addDays(rand(32, 40));
            }
            if (in_array($status, ['approved','point_granted'])) {
                $data['approved_at'] = $appliedAt->copy()->addDays(rand(42, 50));
            }

            $applications[] = Application::create($data);
        }

        // ===== 報告 15件 =====
        $reportBodies = [
            'フェイシャルエステを体験してきました。施術師の方がとても丁寧で、肌がもちもちになりました。毛穴が目立たなくなり、お化粧のりが格段によくなったと感じています。施術中は心地よい香りに包まれリラックスできました。次回もぜひ利用したいです。',
            'ヘアカラートリートメントを1ヶ月使用しました。週2回使用したところ、3週間目から白髪が目立たなくなってきました。色持ちも良く、髪のパサつきも改善されたように感じます。香りも優しくて気に入っています。',
            'まつ毛エクステの施術を受けてきました。担当の方がまつ毛の状態を丁寧に確認してくださり、自分に合ったデザインを提案していただきました。仕上がりはとても自然で気に入っています。3週間たっても持ちがよく満足しています。',
            '脱毛サロンの体験に行ってきました。初回ということもあり、スタッフの方が施術の仕組みから丁寧に説明してくれました。痛みも想定より少なく、次回も利用したいと思います。清潔感のある店舗で安心できました。',
            'ジェルネイルの施術を体験しました。デザインの相談から丁寧にしていただき、仕上がりも大満足です。2週間経ってもほとんど欠けていません。スタッフの方もとても感じよく、また利用したいです。',
            'リンパマッサージを体験しました。全身コース60分で、施術後はとてもスッキリとした気分になりました。翌日の目覚めも良く、顔のむくみが改善されました。定期的に通いたいと思えるサロンでした。',
            '美容液を1ヶ月試しました。朝晩の洗顔後に使用しましたが、2週間目から肌のハリが出てきた気がします。成分が優しくて敏感肌の私でも安心して使えました。テクスチャーも軽くてべたつかないのが好印象です。',
            'ホワイトニングを体験してきました。30分の施術で、歯の色が明らかに明るくなりました。スタッフの方の説明も丁寧で、施術中も痛みや違和感はありませんでした。',
            'ヘッドスパを体験しました。頭皮のコリがほぐれ、施術中は眠ってしまいそうなくらいリラックスできました。翌日の朝、髪の毛がまとまりやすくなっていました。頭皮の乾燥も改善されたように感じます。',
            '痩身エステを体験しました。お腹と太もものケアをしていただき、施術直後からお腹周りが少し引き締まった感じがあります。スタッフの方の対応も親切で、アフターケアのアドバイスも丁寧でした。',
            'モニター体験のご報告です。施術の質も高く、スタッフの対応も丁寧でした。施設も清潔で安心して利用できました。友人にも勧めたいと思えるサービスでした。',
            '1ヶ月間使用した感想をご報告します。効果は確かに感じられ、継続して使用したいと思います。使いやすいパッケージ設計も好印象でした。',
            '体験させていただきありがとうございました。全体的に満足のいく体験でした。また機会があればぜひ利用したいと思います。',
            '今回のモニター体験はとても良い経験になりました。効果をしっかりと実感できたことが嬉しかったです。',
            '体験レポートをご報告します。施術の前後での変化を実感しています。効果には個人差があると思いますが、私自身は満足しています。',
        ];

        // [application_index, report_status]
        $reportItems = [
            [14,'pending'],[15,'pending'],[16,'pending'],[17,'pending'],[18,'pending'],
            [19,'approved'],[20,'approved'],[21,'approved'],[22,'approved'],[23,'approved'],
            [24,'approved'],[25,'approved'],[26,'approved'],
            [27,'rejected'],[28,'rejected'],
        ];

        foreach ($reportItems as $j => [$appIdx, $reportStatus]) {
            $app = $applications[$appIdx];
            $reportData = [
                'application_id' => $app->id,
                'user_id'        => $app->user_id,
                'campaign_id'    => $app->campaign_id,
                'report_body'    => $reportBodies[$j],
                'status'         => $reportStatus,
            ];

            if (in_array($reportStatus, ['approved', 'rejected'])) {
                $reportData['reviewed_by'] = $admin?->id;
                $reportData['reviewed_at'] = now()->subDays(rand(2, 10));
            }
            if ($reportStatus === 'rejected') {
                $reportData['reject_reason'] = '報告内容が不十分です。施術前後のビフォーアフター写真と、より詳細なレビューを再度ご提出ください。';
            }

            MonitorReport::create($reportData);
        }

        // ===== ポイント 20件 =====

        // ① Earn: approved(19〜23) + point_granted(24〜26) = 8件
        for ($i = 19; $i <= 26; $i++) {
            [$ui, $ci] = $pairs[$i];
            $app = $applications[$i];
            $fee = $campaigns[$ci]->cooperation_fee;

            Point::create([
                'user_id'        => $app->user_id,
                'type'           => 'earn',
                'amount'         => $fee,
                'reason'         => 'モニター報告承認によるポイント付与',
                'application_id' => $app->id,
                'granted_by'     => $admin?->id,
                'created_at'     => now()->subDays(rand(1, 25)),
            ]);

            $users[$ui]->increment('point_balance', $fee);
        }

        // ② 手動調整 (adjust) 9件
        $adjustRows = [
            [0,  1000, 'システム移行に伴うポイント調整'],
            [1,  2000, 'キャンペーン参加ボーナスポイント'],
            [2,   500, '紹介ボーナスポイント'],
            [3, -1000, 'キャンセルによるポイント調整'],
            [4,  3000, '長期モニター継続ボーナス'],
            [5,  1500, 'イベント参加ボーナス'],
            [6,  1000, '初回登録記念ポイント'],
            [7,   300, 'アンケート回答ボーナス'],
            [8,  -500, '誤付与ポイントの修正'],
        ];

        foreach ($adjustRows as [$ui, $amount, $reason]) {
            Point::create([
                'user_id'    => $users[$ui]->id,
                'type'       => 'adjust',
                'amount'     => $amount,
                'reason'     => $reason,
                'created_at' => now()->subDays(rand(10, 60)),
            ]);
            $amount > 0
                ? $users[$ui]->increment('point_balance', $amount)
                : $users[$ui]->decrement('point_balance', abs($amount));
        }

        // ③ 換金申請 (exchange) 3件
        foreach ([[10, 3000], [11, 5000], [12, 2000]] as [$ui, $amount]) {
            Point::create([
                'user_id'    => $users[$ui]->id,
                'type'       => 'exchange',
                'amount'     => -$amount,
                'reason'     => '協力金の換金申請',
                'created_at' => now()->subDays(rand(5, 30)),
            ]);
        }

        $this->command->info('✓ モニター会員 20件');
        $this->command->info('✓ 案件 10件（全て公開済み）');
        $this->command->info('✓ 応募 30件（pending:8 / selected:1 / scheduled:4 / completed:1 / reported:5 / approved:5 / point_granted:3 / rejected:3）');
        $this->command->info('✓ 報告 15件（pending:5 / approved:8 / rejected:2）');
        $this->command->info('✓ ポイント 20件（earn:8 / adjust:9 / exchange:3）');
    }
}
