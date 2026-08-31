<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id', 'admin_name', 'action', 'model', 'model_id', 'label', 'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getModelLabel(): string
    {
        return self::modelLabel($this->model);
    }

    public static function modelLabel(string $modelClass): string
    {
        $short = class_basename($modelClass);

        return match ($short) {
            'Campaign'             => '案件',
            'Application'          => '応募',
            'MonitorReport'        => 'モニター報告',
            'CollectionReport'     => '回収報告',
            'User'                 => 'ユーザー',
            'Admin'                => '管理者',
            'Agent'                => '代理店',
            'AgentReferralCode'    => '代理店招待コード',
            'CampaignCourse'       => 'コース',
            'CampaignDailySlot'    => '日別件数',
            'CampaignApprovalReflection' => '承認反映',
            'AdAgencyShareUser'    => '広告代理店共有アカウント',
            'UserReferralReward'   => 'ユーザー招待報酬',
            'Faq'                  => 'よくある質問',
            'GuidePage'            => 'ガイドページ',
            default                => $short,
        };
    }
}
