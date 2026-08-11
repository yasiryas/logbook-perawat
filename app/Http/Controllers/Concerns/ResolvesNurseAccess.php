<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\UserRole;
use App\Models\Nurse;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesNurseAccess
{
    /**
     * Query for the nurses visible to the authenticated user.
     *
     * @return Builder<Nurse>
     */
    protected function visibleNurses(): Builder
    {
        $user = auth()->user();

        return match ($user->role()) {
            UserRole::Admin => Nurse::query(),
            UserRole::KepalaRuang => Nurse::query()->where('unit_id', $user->unit_id),
            UserRole::Perawat => Nurse::query()->where('user_id', $user->id),
        };
    }

    /**
     * Authorization-friendly nurse lookup for route-model-bound pages.
     */
    protected function resolveNurse(Nurse $nurse): Nurse
    {
        abort_unless($this->canAccessNurse($nurse), 403);

        return $nurse;
    }

    protected function canAccessNurse(Nurse $nurse): bool
    {
        $user = auth()->user();

        return match ($user->role()) {
            UserRole::Admin => true,
            UserRole::KepalaRuang => $user->unit_id === null || $nurse->unit_id === $user->unit_id,
            UserRole::Perawat => $user->nurse?->id === $nurse->id,
        };
    }

    /**
     * The nurse a perawat must work with, when a scope default is needed.
     */
    protected function defaultNurse(): ?Nurse
    {
        return $this->visibleNurses()->first();
    }

    /**
     * Nurse switcher options for kepala/admin (empty for perawat).
     *
     * @return array<int, array{id: int, name: string}>
     */
    protected function nurseOptions(): array
    {
        $user = auth()->user();

        if ($user->role() === UserRole::Perawat) {
            return [];
        }

        return $this->visibleNurses()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Nurse $nurse) => ['id' => $nurse->id, 'name' => $nurse->name])
            ->all();
    }
}
