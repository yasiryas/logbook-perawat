<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $unit_id
 * @property string $name
 * @property string $nip
 * @property string $pk
 * @property-read Unit $unit
 * @property-read User|null $user
 * @property-read Collection<int, LogbookEntry> $entries
 */
#[Fillable(['user_id', 'unit_id', 'name', 'nip', 'pk'])]
class Nurse extends Model
{
    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LogbookEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }

    /**
     * @return HasMany<MonthlyConfig, $this>
     */
    public function monthlyConfigs(): HasMany
    {
        return $this->hasMany(MonthlyConfig::class);
    }

    /**
     * @return HasMany<AnnualAssessment, $this>
     */
    public function annualAssessments(): HasMany
    {
        return $this->hasMany(AnnualAssessment::class);
    }
}
