<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $zone_id
 * @property string|null $head_first_name
 * @property string|null $head_middle_name
 * @property string $head_last_name
 * @property string|null $head_suffix
 * @property string|null $contact_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Patient> $patients
 * @property-read int|null $patients_count
 * @property-read Zone|null $zone
 * @property-read Collection<int, HouseholdMember> $members
 * @property-read string $display_name
 * @property-read int $resident_count
 * @property-read int $patient_count
 */
class Household extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'zone_id',
        'head_first_name',
        'head_middle_name',
        'head_last_name',
        'head_suffix',
        'contact_number',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'household_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => fullName(
                $this->head_last_name,
                $this->head_first_name,
                $this->head_middle_name,
                $this->head_suffix,
            ),
        );
    }

    public function residentCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->members()->whereNull('deleted_at')->count(),
        );
    }

    public function patientCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->patients()->whereNull('deleted_at')->count(),
        );
    }
}
