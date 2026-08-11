<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property-read Collection<int, Nurse> $nurses
 * @property-read Collection<int, Indicator> $indicators
 */
#[Fillable(['name'])]
class Unit extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Unit $unit) {
            $unit->slug = Str::slug($unit->name);
        });
    }

    /**
     * @return HasMany<Nurse, $this>
     */
    public function nurses(): HasMany
    {
        return $this->hasMany(Nurse::class);
    }

    /**
     * @return HasMany<Indicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
