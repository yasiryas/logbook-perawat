<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $nurse_id
 * @property int $year
 * @property AssessmentStatus $status
 * @property string|null $assessor_name
 * @property string|null $assessor_nip
 * @property string|null $assessor_position
 * @property string|null $assessor_unit
 * @property string|null $superior_name
 * @property string|null $superior_nip
 * @property string|null $superior_position
 * @property string|null $superior_unit
 * @property-read Carbon|null $finalized_at
 * @property-read Nurse $nurse
 * @property-read Collection<int, AnnualAssessmentItem> $items
 */
#[Fillable([
    'nurse_id',
    'year',
    'status',
    'assessor_name',
    'assessor_nip',
    'assessor_position',
    'assessor_unit',
    'superior_name',
    'superior_nip',
    'superior_position',
    'superior_unit',
    'finalized_at',
])]
class AnnualAssessment extends Model
{
    /**
     * @return BelongsTo<Nurse, $this>
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class);
    }

    /**
     * @return HasMany<AnnualAssessmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AnnualAssessmentItem::class, 'assessment_id');
    }

    protected function casts(): array
    {
        return [
            'status' => AssessmentStatus::class,
            'finalized_at' => 'datetime',
        ];
    }

    public function isFinal(): bool
    {
        return $this->status === AssessmentStatus::Final;
    }

    public function totalScore(): float
    {
        return (float) $this->items->sum('score');
    }
}
