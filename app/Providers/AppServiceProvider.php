<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen('eloquent.created: *', fn ($event, array $data) => $this->recordAudit('created', $data[0]));
        Event::listen('eloquent.updated: *', fn ($event, array $data) => $this->recordAudit('updated', $data[0]));
        Event::listen('eloquent.deleted: *', fn ($event, array $data) => $this->recordAudit('deleted', $data[0]));
    }

    // 管理画面（webガード）でログイン中の管理者が行った変更のみを記録する。
    // cron/キュー/会員（liffガード）側の書き込みは対象外（Auth::guard('web')->check()がfalseになるため自然に除外される）
    private function recordAudit(string $action, Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $admin = Auth::guard('web')->user();
        if (!$admin) {
            return;
        }

        // $hidden（password/remember_token等）に加えて、$hiddenに含まれていない機微情報も念のため除外する
        $sensitiveKeys = ['bank_account_number', 'bank_account_name'];
        $ignoreKeys = array_unique(array_merge($model->getHidden(), $sensitiveKeys, ['created_at', 'updated_at']));

        $changes = match ($action) {
            'created' => collect($model->getAttributes())->except($ignoreKeys)->toArray(),
            'deleted' => collect($model->getOriginal())->except($ignoreKeys)->toArray(),
            'updated' => collect($model->getChanges())->except($ignoreKeys)->keys()
                ->mapWithKeys(fn ($key) => [$key => [
                    'from' => $model->getOriginal($key),
                    'to'   => $model->getAttribute($key),
                ]])->toArray(),
        };

        if ($action === 'updated' && empty($changes)) {
            return;
        }

        AuditLog::create([
            'admin_id'   => $admin->id,
            'admin_name' => $admin->name,
            'action'     => $action,
            'model'      => get_class($model),
            'model_id'   => $model->getKey(),
            'label'      => $this->resolveLabel($model),
            'changes'    => $changes,
        ]);
    }

    private function resolveLabel(Model $model): ?string
    {
        foreach (['title', 'name', 'email', 'bimoni_user_id'] as $field) {
            if (!empty($model->getAttribute($field))) {
                return (string) $model->getAttribute($field);
            }
        }

        return null;
    }
}
