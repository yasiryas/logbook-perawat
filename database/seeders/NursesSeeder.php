<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Nurse;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed 25 perawat (nurses) plus one login account each, spread across the
 * existing units. Safe to re-run: both nurses and users are upserted on
 * unique keys.
 */
class NursesSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::orderBy('id')->get(['id', 'name']);

        if ($units->isEmpty()) {
            $this->command->warn('Tidak ada unit; lewati seeder perawat.');

            return;
        }

        $names = [
            'Ayu Lestari', 'Bambang Prasetyo', 'Citra Dewi', 'Dedi Kurniawan', 'Eka Wulandari',
            'Fitri Rahmawati', 'Gunawan Saputra', 'Hesti Purnamasari', 'Indah Permata', 'Joko Susilo',
            'Kartika Sari', 'Lukman Hakim', 'Maya Anggraini', 'Nurul Hidayah', 'Oki Ramadhan',
            'Putri Maharani', 'Qori Amalia', 'Rizki Pratama', 'Siti Nurjanah', 'Teguh Santoso',
            'Umi Kalsum', 'Vina Oktaviani', 'Wahyu Nugroho', 'Yuli Astuti', 'Zainal Arifin',
        ];

        foreach (array_values($names) as $index => $name) {
            $unit = $units[$index % $units->count()];
            $nip = (string) (2000000001 + $index);
            $pk = 'PK-'.str_pad((string) ($index + 2), 4, '0', STR_PAD_LEFT);
            $email = strtolower('perawat'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'@mail.com');

            $user = $this->createUser($email, $name, $unit->id);

            Nurse::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'unit_id' => $unit->id,
                    'name' => $name,
                    'nip' => $nip,
                    'pk' => $pk,
                ],
            );
        }
    }

    /**
     * Create a perawat account following the application rules: verified
     * email, a personal team with the owner role, and a current team so the
     * redirect after login works.
     */
    private function createUser(string $email, string $name, int $unitId): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => '123456',
                'role' => UserRole::Perawat->value,
                'unit_id' => $unitId,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->currentTeam) {
            $team = Team::firstOrCreate(
                ['slug' => Str::slug($email)],
                [
                    'name' => "Team {$name}",
                    'is_personal' => true,
                ],
            );

            if (! $user->belongsToTeam($team)) {
                $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
            }

            $user->switchTeam($team);
        }

        return $user;
    }
}