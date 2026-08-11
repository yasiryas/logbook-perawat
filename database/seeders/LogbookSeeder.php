<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Nurse;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LogbookSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $unitRanap = Unit::firstOrCreate(['slug' => Str::slug('RANAP (Rawat Inap)')], ['name' => 'RANAP (Rawat Inap)']);
        $unitRajal = Unit::firstOrCreate(['slug' => Str::slug('RAJAL (Rawat Jalan)')], ['name' => 'RAJAL (Rawat Jalan)']);

        $this->seedGlobalIndicators($categories);

        $this->seedUnitIndicators($categories['C'], $unitRanap, $this->ranapIndicators());
        $this->seedUnitIndicators($categories['C'], $unitRajal, $this->rajalIndicators());

        $this->seedUsers($unitRanap);
    }

    /**
     * @return array<string, IndicatorCategory>
     */
    private function seedCategories(): array
    {
        $data = [
            ['code' => 'A', 'name' => 'Perilaku', 'weight' => 40],
            ['code' => 'B', 'name' => 'Pengembangan Profesi', 'weight' => 10],
            ['code' => 'C', 'name' => 'Kinerja Klinis', 'weight' => 50],
        ];

        $categories = [];

        foreach ($data as $item) {
            $categories[$item['code']] = IndicatorCategory::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'weight_percent' => $item['weight'],
                ],
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, IndicatorCategory>  $categories
     */
    private function seedGlobalIndicators(array $categories): void
    {
        $indicators = [
            ['category' => 'A', 'name' => 'Melaporkan insiden keselamatan pasien', 'target' => '100'],
            ['category' => 'A', 'name' => 'Tidak ada keluhan dari teman sejawat/profesi lain/pasien dan keluarga', 'target' => '100'],
            ['category' => 'A', 'name' => 'Kepatuhan terhadap kode etik keperawatan', 'target' => '100'],
            ['category' => 'B', 'name' => 'Menunjukan perilaku bertanggung gugat terhadap praktek profesional', 'target' => '100'],
            ['category' => 'B', 'name' => 'Kepatuhan dalam memberikan pendidikan pasien dan keluarga', 'target' => '100'],
        ];

        foreach ($indicators as $index => $item) {
            Indicator::firstOrCreate([
                'category_id' => $categories[$item['category']]->id,
                'unit_id' => null,
                'name' => $item['name'],
            ], [
                'sort' => $index + 1,
                'target' => $item['target'],
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, target?: string}>  $items
     */
    private function seedUnitIndicators(IndicatorCategory $category, Unit $unit, array $items): void
    {
        foreach ($items as $index => $item) {
            Indicator::firstOrCreate([
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'name' => $item['name'],
            ], [
                'sort' => $index + 1,
                'target' => $item['target'] ?? '100',
            ]);
        }
    }

    /**
     * @return array<int, array{name: string, target?: string}>
     */
    private function ranapIndicators(): array
    {
        return [
            ['name' => 'Pengkajian Pasien'],
            ['name' => 'Menetapkan diagnosa Keperawatan'],
            ['name' => 'Menyusun rencana keperawatan'],
            ['name' => 'Melakukan tindakan keperawatan'],
            ['name' => 'Melakukan dokumentasi keperawatan'],
            ['name' => 'Kepatuhan identifikasi pasien'],
            ['name' => 'Kepatuhan pemakaian APD dengan benar'],
            ['name' => 'Kepatuhan kebersihan tangan', 'target' => '>85'],
            ['name' => 'Kepatuhan upaya pencegahan resiko pasien jatuh'],
            ['name' => 'Kepatuhan melaksanakan hand over antar petugas dengan metode SBAR'],
            ['name' => 'Kepatuhan melaksanakan double chek obat high allert'],
            ['name' => 'Kemampuan menangani life saving anak dan dewasa'],
            ['name' => 'Respon Time / kecepatan pelayanan di Gawat Darurat (5 menit)', 'target' => '<5 mnt'],
        ];
    }

    /**
     * @return array<int, array{name: string, target?: string}>
     */
    private function rajalIndicators(): array
    {
        return [
            ['name' => 'Pengkajian Pasien'],
            ['name' => 'Menetapkan diagnosa Keperawatan'],
            ['name' => 'Menyusun rencana keperawatan'],
            ['name' => 'Melakukan tindakan keperawatan'],
            ['name' => 'Melakukan dokumentasi keperawatan'],
            ['name' => 'Kepatuhan identifikasi pasien'],
            ['name' => 'Kepatuhan pemakaian APD dengan benar'],
            ['name' => 'Kepatuhan kebersihan tangan', 'target' => '>85'],
            ['name' => 'Kepatuhan upaya pencegahan resiko pasien jatuh'],
            ['name' => 'Kepatuhan melaksanakan hand over antar petugas dengan metode SBAR'],
            ['name' => 'Kepatuhan melaksanakan double chek obat high allert'],
            ['name' => 'Kemampuan menangani life saving anak dan dewasa'],
            ['name' => 'Kecepatan pelayanan pasien dengan risiko tinggi', 'target' => '<5 mnt'],
        ];
    }

    /**
     * Seed demo accounts. All use the password "123456".
     */
    private function seedUsers(Unit $unitRanap): void
    {
        $admin = $this->createUser('admin@mail.com', 'Administrator', UserRole::Admin, null);

        $head = $this->createUser(
            'kepala_ruang@mail.com',
            'Kepala Ruang RANAP',
            UserRole::KepalaRuang,
            $unitRanap->id,
        );

        $nurseUser = $this->createUser(
            'perawat@mail.com',
            'Perawat RANAP',
            UserRole::Perawat,
            $unitRanap->id,
        );

        Nurse::firstOrCreate(
            ['user_id' => $nurseUser->id],
            [
                'unit_id' => $unitRanap->id,
                'name' => $nurseUser->name,
                'nip' => '1000000001',
                'pk' => 'PK-0001',
            ],
        );
    }

    /**
     * Create a user following the application rules: verified email, a
     * personal team with the owner role, and a current team so the
     * redirect after login works.
     */
    private function createUser(string $email, string $name, UserRole $role, ?int $unitId): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => '123456',
                'role' => $role->value,
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
