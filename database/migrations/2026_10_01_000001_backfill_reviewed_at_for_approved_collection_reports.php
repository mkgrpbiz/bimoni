<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 回収報告の月締めを報告日時(created_at)から承認日時(reviewed_at)に変更するにあたり、
        // インポート経由で作成された承認済みレコードはreviewed_atが未設定のままだったため、
        // created_atで代用してバックフィルする（2026-10-01）
        DB::statement("
            UPDATE collection_reports
            SET reviewed_at = created_at
            WHERE status = 'approved' AND reviewed_at IS NULL
        ");
    }

    public function down(): void
    {
        // 元に戻す安全な方法が無いため何もしない（このマイグレーションはデータ補完のみ）
    }
};
