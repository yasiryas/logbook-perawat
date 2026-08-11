<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\LogbookEntry;
use App\Models\MonthlyConfig;
use App\Models\Nurse;
use App\Models\Unit;
use App\Services\LogbookCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogbookCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nurse dengan 2 indikator kategori A, working days 28, bulan Januari 2025.
     *
     * @return array{nurse: Nurse, indicatorIds: array<int, int>}
     */
    private function makeSubject(): array
    {
        $unit = Unit::create(['name' => 'RAJAL (Rawat Jalan)']);
        $category = IndicatorCategory::create(['code' => 'A', 'name' => 'Perilaku', 'weight_percent' => 40]);

        $indicatorIds = [];
        foreach (['Indikator 1', 'Indikator 2'] as $index => $name) {
            $indicator = Indicator::create([
                'category_id' => $category->id,
                'unit_id' => null,
                'sort' => $index + 1,
                'name' => $name,
                'target' => '100',
            ]);
            $indicatorIds[$index] = $indicator->id;
        }

        $nurse = Nurse::create([
            'unit_id' => $unit->id,
            'name' => 'Ns. Test',
            'nip' => '198001012000012001',
            'pk' => 'PK.0001',
        ]);

        MonthlyConfig::create([
            'nurse_id' => $nurse->id,
            'month' => 1,
            'year' => 2025,
            'working_days' => 28,
        ]);

        return ['nurse' => $nurse, 'indicatorIds' => $indicatorIds];
    }

    public function test_monthly_achievement_is_sum_of_daily_values_divided_by_working_days(): void
    {
        $subject = $this->makeSubject();
        $nurse = $subject['nurse'];
        $ids = $subject['indicatorIds'];

        $this->addEntry($nurse, $ids[0], '2025-01-01', 1);
        $this->addEntry($nurse, $ids[0], '2025-01-02', 1);
        $this->addEntry($nurse, $ids[1], '2025-01-01', 1);

        $recap = app(LogbookCalculationService::class)->monthlyRecap($nurse, 1, 2025);

        $this->assertSame(28, $recap['working_days']);
        $this->assertCount(1, $recap['categories']);

        $category = $recap['categories'][0];
        $indicatorRows = collect($category['indicators'])->keyBy(fn ($row) => $row['indicator']->id);

        // Indikator 1: 2 hari x 1 = 2 / 28
        $this->assertEqualsWithDelta(2 / 28, $indicatorRows[$ids[0]]['achievement'], 0.000001);
        $this->assertEqualsWithDelta(2.0, $indicatorRows[$ids[0]]['sum'], 0.000001);

        // Indikator 2: 1 hari x 1 = 1 / 28
        $this->assertEqualsWithDelta(1 / 28, $indicatorRows[$ids[1]]['achievement'], 0.000001);

        // Rata-rata kategori = mean capaian kedua indikator
        $expectedCategory = (2 / 28 + 1 / 28) / 2;
        $this->assertEqualsWithDelta($expectedCategory, $category['achievement'], 0.000001);

        // Total = jumlah capaian kategori (format kertas lama)
        $this->assertEqualsWithDelta($expectedCategory, $recap['total'], 0.000001);
    }

    public function test_empty_month_has_zero_achievement(): void
    {
        $subject = $this->makeSubject();
        $recap = app(LogbookCalculationService::class)->monthlyRecap($subject['nurse'], 1, 2025);

        $this->assertSame(0.0, $recap['total']);
    }

    public function test_annual_achievement_is_average_of_twelve_monthly_achievements(): void
    {
        $subject = $this->makeSubject();
        $nurse = $subject['nurse'];
        $ids = $subject['indicatorIds'];

        $this->addEntry($nurse, $ids[0], '2025-01-01', 1);
        $this->addEntry($nurse, $ids[0], '2025-01-02', 1);

        MonthlyConfig::create([
            'nurse_id' => $nurse->id,
            'month' => 5,
            'year' => 2025,
            'working_days' => 28,
        ]);

        $this->addEntry($nurse, $ids[0], '2025-05-01', 1);

        $annual = app(LogbookCalculationService::class)->annualPerIndicator($nurse, 2025);

        $row = collect($annual)->first(fn ($row) => $row['indicator']->id === $ids[0]);

        // Januari: 2/28; Mei: 1/28; bulan lain 0 -> dibagi 12
        $expected = (2 / 28 + 1 / 28) / 12;

        $this->assertEqualsWithDelta($expected, $row['achievement'], 0.000001);
        $this->assertCount(12, $row['monthly']);
    }

    public function test_assessment_rows_apply_category_weight(): void
    {
        $subject = $this->makeSubject();
        $nurse = $subject['nurse'];
        $ids = $subject['indicatorIds'];

        $this->addEntry($nurse, $ids[0], '2025-01-01', 1);

        $rows = app(LogbookCalculationService::class)->assessmentRows($nurse, 2025);
        $category = $rows[0];

        $this->assertSame(40, $category['weight_percent']);

        foreach ($category['rows'] as $row) {
            $this->assertEqualsWithDelta(0.4, $row['weight'], 0.000001);
            $this->assertEqualsWithDelta($row['achievement'] * 0.4, $row['score'], 0.000001);
        }
    }

    private function addEntry(Nurse $nurse, int $indicatorId, string $date, float $value): void
    {
        LogbookEntry::create([
            'nurse_id' => $nurse->id,
            'indicator_id' => $indicatorId,
            'date' => $date,
            'value' => $value,
        ]);
    }
}
