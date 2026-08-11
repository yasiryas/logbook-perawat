<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $nurse_id
 * @property int $indicator_id
 * @property string $date
 * @property float|null $value
 * @property-read Nurse $nurse
 * @property-read Indicator $indicator
 */
#[Fillable(['nurse_id', 'indicator_id', 'date', 'value'])]
class LogbookEntry extends Model
{
    /**
     * @return BelongsTo<Nurse, $this>
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class);
    }

    /**
     * @return BelongsTo<Indicator, $this>
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
