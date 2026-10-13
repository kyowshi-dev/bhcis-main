<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $diagnosis_code
 * @property string $diagnosis_name
 * @property string|null $category
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DiagnosisRecord> $diagnosisRecords
 * @property-read Collection<int, Death> $deaths
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereDiagnosisCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereDiagnosisName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisLookup whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DiagnosisLookup extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'diagnosis_lookup';

    protected $fillable = [
        'diagnosis_code',
        'diagnosis_name',
        'category',
    ];

    public function diagnosisRecords(): HasMany
    {
        return $this->hasMany(DiagnosisRecord::class, 'diagnosis_id');
    }

    public function deaths(): HasMany
    {
        return $this->hasMany(Death::class, 'cause_diagnosis_id');
    }
}
