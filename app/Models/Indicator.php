<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $category_id
 * @property int|null $unit_id
 * @property int $sort
 * @property string $name
 * @property string $target
 * @property bool $is_global
 * @property-read IndicatorCategory $category
 * @property-read Unit|null $unit
 * @property-read Collection<int, LogbookEntry> $entries
 */
#[Fillable(['category_id', 'unit_id', 'sort', 'name', 'target'])]
class Indicator extends Model
{
    /**
     * @return BelongsTo<IndicatorCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IndicatorCategory::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<LogbookEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }

    /**
     * True when the indicator applies to all units (categories A & B).
     */
    public function getIsGlobalAttribute(): bool
    {
        return $this->unit_id === null;
    }

    /**
     * Daily values are always entered as numbers (counts).
     */
    public function isCheckbox(): bool
    {
        return false;
    }
}
