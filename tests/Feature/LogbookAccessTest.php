<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Nurse;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogbookAccessTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private Unit $otherUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['name' => 'RAJAL']);
        $this->otherUnit = Unit::create(['name' => 'IGD']);
        $category = IndicatorCategory::create(['code' => 'A', 'name' => 'Perilaku', 'weight_percent' => 40]);
        Indicator::create([
            'category_id' => $category->id,
            'unit_id' => null,
            'sort' => 1,
            'name' => 'Indikator 1',
            'target' => '100',
        ]);
    }

    private function makeNurse(Unit $unit, ?User $user = null): Nurse
    {
        return Nurse::create([
            'unit_id' => $unit->id,
            'name' => 'Ns. Test',
            'nip' => 'NIP-'.rand(100000, 999999),
            'pk' => 'PK.0001',
            'user_id' => $user?->id,
        ]);
    }

    private function makeUser(UserRole $role, ?Unit $unit = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'unit_id' => $unit?->id,
        ]);
    }

    public function test_perawat_can_access_own_logbook(): void
    {
        $user = $this->makeUser(UserRole::Perawat);
        $nurse = $this->makeNurse($this->unit, $user);

        $this->actingAs($user)
            ->get('/logbook')
            ->assertOk();
    }

    public function test_perawat_cannot_access_other_nurses(): void
    {
        $user = $this->makeUser(UserRole::Perawat);
        $this->makeNurse($this->unit, $user);
        $other = $this->makeNurse($this->unit);

        $this->actingAs($user)
            ->get("/logbook/{$other->id}")
            ->assertForbidden();
    }

    public function test_kepala_ruang_can_access_nurses_in_own_unit_only(): void
    {
        $kepala = $this->makeUser(UserRole::KepalaRuang, $this->unit);
        $own = $this->makeNurse($this->unit);
        $foreign = $this->makeNurse($this->otherUnit);

        $this->actingAs($kepala)->get("/logbook/{$own->id}")->assertOk();
        $this->actingAs($kepala)->get("/recaps/annual/{$foreign->id}")->assertForbidden();
    }

    public function test_admin_can_access_any_nurse(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $nurse = $this->makeNurse($this->otherUnit);

        $this->actingAs($admin)->get("/logbook/{$nurse->id}")->assertOk();
    }

    public function test_perawat_cannot_open_master_pages(): void
    {
        $user = $this->makeUser(UserRole::Perawat);

        $this->actingAs($user)->get('/units')->assertForbidden();
        $this->actingAs($user)->get('/nurses')->assertForbidden();
        $this->actingAs($user)->get('/indicators')->assertForbidden();
    }

    public function test_logbook_update_saves_values(): void
    {
        $user = $this->makeUser(UserRole::Perawat);
        $nurse = $this->makeNurse($this->unit, $user);
        $indicator = Indicator::query()->whereNull('unit_id')->firstOrFail();

        $this->actingAs($user)->put("/logbook/{$nurse->id}", [
            'month' => 1,
            'year' => 2026,
            'working_days' => 25,
            'values' => [$indicator->id => [1 => 1, 2 => 0.5]],
        ])->assertRedirect();

        $this->assertDatabaseHas('monthly_configs', [
            'nurse_id' => $nurse->id,
            'month' => 1,
            'year' => 2026,
            'working_days' => 25,
        ]);

        $this->assertDatabaseCount('logbook_entries', 2);

        $this->assertDatabaseHas('logbook_entries', [
            'nurse_id' => $nurse->id,
            'indicator_id' => $indicator->id,
            'date' => '2026-01-01 00:00:00',
            'value' => 1.0,
        ]);

        $this->assertDatabaseHas('logbook_entries', [
            'nurse_id' => $nurse->id,
            'indicator_id' => $indicator->id,
            'date' => '2026-01-02 00:00:00',
            'value' => 0.5,
        ]);
    }
}
