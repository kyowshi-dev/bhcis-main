<?php

namespace App\Traits;

use App\Services\AuditLogService;

trait LogsActivity
{
    /**
     * Columns to redact from audit log old/new values (e.g. password hashes).
     *
     * @var array<int, string>
     */
    protected $auditHiddenAttributes = ['password', 'remember_token'];

    protected static function bootLogsActivity()
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                // FIX: If it's an update, skip logging if nothing actually changed except the timestamp
                if ($event === 'updated') {
                    $dirty = $model->getDirty();
                    unset($dirty['updated_at']);
                    if (empty($dirty)) {
                        return;
                    }
                }

                /** @var AuditLogService $service */
                $service = app(AuditLogService::class);

                match ($event) {
                    'created' => $service->recordCreated($model),
                    'updated' => $service->recordUpdated($model),
                    'deleted' => $service->recordDeleted($model),
                };
            });
        }
    }

    /**
     * Strip sensitive columns from values before persisting them to the audit log.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function redactAuditValues(array $values): array
    {
        foreach ($this->auditHiddenAttributes as $attribute) {
            unset($values[$attribute]);
        }

        return $values;
    }
}
