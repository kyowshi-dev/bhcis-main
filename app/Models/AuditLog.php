<?php

namespace App\Models;

use App\Support\AuditTaxonomy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $table_name
 * @property int|null $record_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string $category
 * @property string $severity
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read string $formatted_activity
 * @property-read string $description
 * @property-read User|null $user
 *
 * @method static Builder<static>|AuditLog newModelQuery()
 * @method static Builder<static>|AuditLog newQuery()
 * @method static Builder<static>|AuditLog query()
 * @method static Builder<static>|AuditLog whereAction($value)
 * @method static Builder<static>|AuditLog whereCategory($value)
 * @method static Builder<static>|AuditLog whereCreatedAt($value)
 * @method static Builder<static>|AuditLog whereId($value)
 * @method static Builder<static>|AuditLog whereIpAddress($value)
 * @method static Builder<static>|AuditLog whereNewValues($value)
 * @method static Builder<static>|AuditLog whereOldValues($value)
 * @method static Builder<static>|AuditLog whereRecordId($value)
 * @method static Builder<static>|AuditLog whereSeverity($value)
 * @method static Builder<static>|AuditLog whereTableName($value)
 * @method static Builder<static>|AuditLog whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AuditLog extends Model
{
    use Prunable;

    protected $table = 'audit_logs';

    /**
     * Get the query for pruning expired audit entries (retention policy).
     */
    public function prunable(): Builder
    {
        $retentionDays = (int) config('bhcis.audit_retention_days', 365);

        return static::query()->where('created_at', '<', now()->subDays($retentionDays));
    }

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'category',
        'severity',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function getFormattedActivityAttribute(): string
    {
        $time = Carbon::parse($this->created_at)->format('M d, Y H:i');
        $user = $this->user?->username ?: 'System';
        $action = ucfirst($this->action);
        $table = ucfirst(str_replace('_', ' ', $this->table_name));
        $record = $this->record_id !== null ? " #{$this->record_id}" : '';

        return "{$time} – {$user} {$action} {$table}{$record}";
    }

    /**
     * Human-readable description of the audit event.
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user?->username ?: 'System';
        $event = AuditTaxonomy::describeEvent($this->action, $this->table_name, $this->record_id);

        // CRUD events read as "Alice created Patient #1"; semantic events as "Alice - Sign in"
        if (in_array($this->action, ['created', 'updated', 'deleted'], true)) {
            return "{$user} {$event}";
        }

        return "{$user} - {$event}";
    }

    /**
     * Badge specification for UI rendering.
     *
     * @return array{label: string, icon: string, classes: string}
     */
    public function badgeSpec(): array
    {
        $taxonomy = AuditTaxonomy::classify($this->action, $this->table_name);

        return [
            'label' => $taxonomy['label'],
            'icon' => $taxonomy['icon'],
            'classes' => AuditTaxonomy::badgeClasses($this->severity, $this->category),
        ];
    }

    /**
     * Get the record link if available and viewer has permission.
     */
    public function recordLink(?User $viewer = null): ?string
    {
        $link = AuditTaxonomy::recordLink($this->table_name);

        if (! $link || ! $this->record_id) {
            return null;
        }

        $viewer = $viewer ?? auth()->user();

        if ($viewer) {
            $permissions = (array) $link['permission'];
            $allowed = collect($permissions)->contains(
                fn (string $permission) => $viewer->hasPermission($permission)
            );

            if (! $allowed) {
                return null;
            }
        }

        return route($link['route'], $this->record_id);
    }

    public $timestamps = false;

    // Disable auto timestamps since we use created_at only
    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
