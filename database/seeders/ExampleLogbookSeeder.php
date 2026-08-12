<?php

namespace Database\Seeders;

use App\Models\LogbookEntry;
use App\Models\MonthlyConfig;
use App\Models\Nurse;
use App\Services\LogbookCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fill each nurse's logbook with realistic example entries for the current
 * year (up to the current month), so the dashboard, rekap and penilaian
 * pages have data to show. Safe to re-run: entries are upserted on the
 * (nurse_id, indicator_id, date) unique key.
 */
class ExampleLogbookSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(LogbookCalculationService::class);
        $today = Carbon::today();
        $firstMonth = max(1, $today->month - 5);

        foreach (Nurse::with('unit')->get() as $nurse) {
            $indicators = $service->indicatorsFor($nurse->unit)->get();

            for ($month = $firstMonth; $month <= $today->month; $month++) {
                $lastDay = $month === $today->month
                    ? $today->day
                    : Carbon::create($today->year, $month)->endOfMonth()->day;

                $workingDays = collect(range(1, $lastDay))->reduce(
                    fn (int $carry, int $day) => $carry + (self::isWeekday($today->year, $month, $day) ? 1 : 0),
                    0
                );

                if ($workingDays === 0) {
                    continue;
                }

                MonthlyConfig::updateOrCreate(
                    ['nurse_id' => $nurse->id, 'month' => $month, 'year' => $today->year],
                    ['working_days' => $workingDays],
                );

                foreach ($indicators as $indicator) {
                    foreach (self::fillDays($nurse->id, $indicator->id, $month, $lastDay) as $day => $value) {
                        LogbookEntry::updateOrCreate(
                            [
                                'nurse_id' => $nurse->id,
                                'indicator_id' => $indicator->id,
                                'date' => Carbon::create($today->year, $month, $day)->toDateString(),
                            ],
                            ['value' => $value],
                        );
                    }
                }
            }
        }
    }

    private static function isWeekday(int $year, int $month, int $day): bool
    {
        $weekday = Carbon::create($year, $month, $day)->dayOfWeek;

        return $weekday !== Carbon::SATURDAY && $weekday !== Carbon::SUNDAY;
    }

    /**
     * Deterministic, pseudo-random daily values for one indicator:
     * mostly 1 (kegiatan terlaksana), kadang 2 (kegiatan ganda), kadang kosong
     * (hari libur kerja / tidak diisi).
     *
     * @return array<int, float>
     */
    private static function fillDays(int $nurseId, int $indicatorId, int $month, int $lastDay): array
    {
        $values = [];

        foreach (range(1, $lastDay) as $day) {
            if (! self::isWeekday(Carbon::today()->year, $month, $day)) {
                continue;
            }

            $seed = ($nurseId * 31 + $indicatorId * 7 + $month * 13 + $day) % 10;

            if ($seed >= 8) {
                continue;
            }

            $values[$day] = $seed === 6 ? 2.0 : 1.0;
        }

        return $values;
    }
}