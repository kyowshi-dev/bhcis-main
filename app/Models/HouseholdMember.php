<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int|null $patient_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $suffix
 * @property string $sex
 * @property Carbon|null $date_of_birth
 * @property int|null $age
 * @property string $relationship
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Household $household
 * @property-read Patient|null $patient
 * @property-read string $full_name
 * @property-read int|null $age
 */
class HouseholdMember extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'household_id',
        'patient_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'date_of_birth',
        'age',
        'relationship',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => fullName($this->last_name, $this->first_name, $this->middle_name, $this->suffix),
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->date_of_birth !== null) {
                    return $this->date_of_birth->age;
                }

                return $value === null ? null : (int) $value;
            },
        );
    }
}
