<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\AuditTaxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogService
{
    /**
     * Log a semantic action (auth, admin, etc.)
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        string $table,
        Request $request,
        ?int $userId = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $category = null,
        ?string $severity = null
    ): void {
        $taxonomy = AuditTaxonomy::classify($action, $table);

        AuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues ? $this->redact($oldValues) : null,
            'new_values' => $newValues ? $this->redact($newValues) : null,
            'ip_address' => $this->ip($request),
            'category' => $category ?? $taxonomy['category'],
            'severity' => $severity ?? $taxonomy['severity'],
            'created_at' => now(),
        ]);
    }

    /**
     * Log model creation
     *
     * @param  array<string, mixed>|null  $newValues  optional override (defaults to model attributes)
     */
    public function recordCreated(Model $model, ?Request $request = null, ?array $newValues = null): void
    {
        $taxonomy = AuditTaxonomy::classify('created', $model->getTable());

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => null,
            'new_values' => $newValues ? $this->redact($newValues) : $this->redact($model->getAttributes()),
            'ip_address' => $this->ip($request),
            'category' => $taxonomy['category'],
            'severity' => $taxonomy['severity'],
            'created_at' => now(),
        ]);
    }

    /**
     * Log model update - stores only changed fields
     */
    public function recordUpdated(Model $model, ?Request $request = null): void
    {
        $dirty = $model->getDirty();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return;
        }

        $original = $model->getOriginal();
        $changedKeys = array_keys($dirty);

        $oldValues = [];
        $newValues = [];

        foreach ($changedKeys as $key) {
            $oldValues[$key] = $original[$key] ?? null;
            $newValues[$key] = $dirty[$key];
        }

        $taxonomy = AuditTaxonomy::classify('updated', $model->getTable());

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => $this->ip($request),
            'category' => $taxonomy['category'],
            'severity' => $taxonomy['severity'],
            'created_at' => now(),
        ]);
    }

    /**
     * Log model deletion
     */
    public function recordDeleted(Model $model, ?Request $request = null): void
    {
        $taxonomy = AuditTaxonomy::classify('deleted', $model->getTable());

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => $this->redact($model->getAttributes()),
            'new_values' => null,
            'ip_address' => $this->ip($request),
            'category' => $taxonomy['category'],
            'severity' => $taxonomy['severity'],
            'created_at' => now(),
        ]);
    }

    /**
     * Resolve the client IP from the given request or the current global request.
     */
    private function ip(?Request $request = null): ?string
    {
        return $request?->ip() ?? request()->ip();
    }

    /**
     * Redact sensitive values from audit payload
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach (['password', 'remember_token'] as $attr) {
            unset($values[$attr]);
        }

        return $values;
    }
}
