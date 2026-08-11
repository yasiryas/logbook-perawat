<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $weight_percent
 * @property-read Collection<int, Indicator> $indicators
 */
#[Fillable(['code', 'name', 'weight_percent'])]
class IndicatorCategory extends Model
{
    /**
     * @return HasMany<Indicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    /**
     * Get the weight as a decimal (e.g. 40 -> 0.40).
     */
    public function weightAsDecimal(): float
    {
        return $this->weight_percent / 100;
    }
}
