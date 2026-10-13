<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property Carbon $date_of_death
 * @property string $place_of_death
 * @property int|null $cause_diagnosis_id
 * @property string|null $cause_text
 * @property bool $maternal_death
 * @property bool $perinatal_death
 * @property bool $neonatal_death
 * @property bool $infant_death
 * @property bool $under_five_death
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 * @property-read DiagnosisLookup|null $causeDiagnosis
 * @property-read HealthWorker|null $recordedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereCauseDiagnosisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereCauseText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereDateOfDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereMaternalDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death wherePerinatalDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereNeonatalDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereInfantDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereUnderFiveDeath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Death wherePlaceOfDeath($value)
 *
 * @mixin \Eloquent
 */
class Death extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'deaths';

    protected $fillable = [
        'patient_id',
        'date_of_death',
        'place_of_death',
        'cause_diagnosis_id',
        'cause_text',
        'maternal_death',
        'perinatal_death',
        'neonatal_death',
        'infant_death',
        'under_five_death',
        'recorded_by',
    ];

    protected $casts = [
        'date_of_death' => 'date',
        'maternal_death' => 'boolean',
        'perinatal_death' => 'boolean',
        'neonatal_death' => 'boolean',
        'infant_death' => 'boolean',
        'under_five_death' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function causeDiagnosis(): BelongsTo
    {
        return $this->belongsTo(DiagnosisLookup::class, 'cause_diagnosis_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'recorded_by');
    }
}
