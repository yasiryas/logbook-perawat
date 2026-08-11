<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assessment_id
 * @property int $indicator_id
 * @property string $target
 * @property float $achievement
 * @property float $weight
 * @property float $score
 * @property-read AnnualAssessment $assessment
 * @property-read Indicator $indicator
 */
#[Fillable(['assessment_id', 'indicator_id', 'target', 'achievement', 'weight', 'score'])]
class AnnualAssessmentItem extends Model
{
    /**
     * @return BelongsTo<AnnualAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AnnualAssessment::class);
    }

    /**
     * @return BelongsTo<Indicator, $this>
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
