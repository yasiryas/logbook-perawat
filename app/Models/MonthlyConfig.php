<?php

namespace App\Models;

use App\Enums\MonthlyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $nurse_id
 * @property int $month
 * @property int $year
 * @property int $working_days
 * @property MonthlyStatus $status
 * @property-read Nurse $nurse
 */
#[Fillable(['nurse_id', 'month', 'year', 'working_days', 'status'])]
class MonthlyConfig extends Model
{
    /**
     * @return BelongsTo<Nurse, $this>
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class);
    }

    protected function casts(): array
    {
        return [
            'status' => MonthlyStatus::class,
        ];
    }
}
