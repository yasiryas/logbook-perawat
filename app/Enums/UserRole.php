<?php

namespace App\Enums;

enum UserRole: string
{
    case Perawat = 'perawat';
    case KepalaRuang = 'kepala_ruang';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Perawat => 'Perawat',
            self::KepalaRuang => 'Kepala Ruang',
            self::Admin => 'Administrator',
        };
    }

    public function canManageMasterData(): bool
    {
        return $this === self::Admin || $this === self::KepalaRuang;
    }

    public function canManageAllNurses(): bool
    {
        return $this === self::Admin || $this === self::KepalaRuang;
    }

    public function canManageAnnualAssessments(): bool
    {
        return $this === self::Admin || $this === self::KepalaRuang;
    }
}
