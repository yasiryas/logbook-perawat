<?php

namespace App\Services;

use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\LogbookEntry;
use App\Models\MonthlyConfig;
use App\Models\Nurse;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogbookCalculationService
{
    /**
     * Build the daily-input grid for a nurse in a given month.
     * Values are keyed by day-of-month; a missing key means the cell is empty.
     *
     * @return array{working_days: int, days_in_month: int, categories: list<array{category: IndicatorCategory, indicators: list<array{indicator: Indicator, values: array<int, float>}>}>}
     */
    public function grid(Nurse $nurse, int $month, int $year): array
    {
        $workingDays = $this->workingDays($nurse, $month, $year);
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        $entryMap = [];

        foreach (LogbookEntry::query()
            ->where('nurse_id', $nurse->id)
            ->whereBetween('date', [
                Carbon::create($year, $month)->startOfMonth()->toDateString(),
                Carbon::create($year, $month)->endOfMonth()->toDateString(),
            ])
            ->get() as $entry) {
            $day = Carbon::parse($entry->date)->day;
            $entryMap[$entry->indicator_id.'-'.$day] = (float) $entry->value;
        }

        $categoryRows = [];

        foreach ($this->indicatorsFor($nurse->unit)->with('category')->get()->sortBy('sort')->values() as $indicator) {
            $values = [];

            foreach ($entryMap as $key => $value) {
                if (str_starts_with($key, $indicator->id.'-')) {
                    $day = (int) substr($key, strlen((string) $indicator->id) + 1);
                    $values[$day] = $value;
                }
            }

            $categoryId = $indicator->category_id;
            $categoryRows[$categoryId]['category'] ??= $indicator->category;
            $categoryRows[$categoryId]['indicators'][] = [
                'indicator' => $indicator,
                'values' => $values,
            ];
        }

        $categories = [];

        foreach ($categoryRows as $row) {
            $categories[] = [
                'category' => $row['category'],
                'indicators' => $row['indicators'],
            ];
        }

        return [
            'working_days' => $workingDays,
            'days_in_month' => $daysInMonth,
            'categories' => $categories,
        ];
    }

    /**
     * Monthly achievement per indicator, grouped by category.
     * Per-indicator achievement = sum of daily values / working days.
     * Category achievement = average of its indicator achievements.
     * Total = sum of category achievements (match format kertas lama).
     *
     * @param  array{working_days: int, days_in_month: int, categories: list<array{category: IndicatorCategory, indicators: list<array{indicator: Indicator, values: array<int, float>}>}>}|null  $grid
     * @return array{working_days: int, categories: list<array{category: IndicatorCategory, weight_percent: int, indicators: list<array{indicator: Indicator, sum: float, filled_days: int, achievement: float}>, achievement: float}>, total: float}
     */
    public function monthlyRecap(Nurse $nurse, int $month, int $year, ?array $grid = null): array
    {
        if ($grid === null) {
            $grid = $this->grid($nurse, $month, $year);
        }

        $workingDays = $grid['working_days'];
        $categories = [];

        foreach ($grid['categories'] as $categoryRow) {
            $indicatorRows = [];

            foreach ($categoryRow['indicators'] as $row) {
                $sum = (float) array_sum($row['values']);

                $indicatorRows[] = [
                    'indicator' => $row['indicator'],
                    'values' => $row['values'],
                    'sum' => $sum,
                    'filled_days' => count($row['values']),
                    'achievement' => $workingDays > 0 ? $sum / $workingDays : 0.0,
                ];
            }

            $categoryAchievement = $indicatorRows === [] ? 0.0 : array_sum(array_column($indicatorRows, 'achievement')) / count($indicatorRows);

            $categories[] = [
                'category' => $categoryRow['category'],
                'weight_percent' => $categoryRow['category']->weight_percent,
                'indicators' => $indicatorRows,
                'achievement' => $categoryAchievement,
            ];
        }

        return [
            'working_days' => $workingDays,
            'categories' => $categories,
            'total' => (float) array_sum(array_column($categories, 'achievement')),
        ];
    }

    /**
     * Annual per-indicator achievement: average of the 12 monthly achievements.
     *
     * @return list<array{indicator: Indicator, category: IndicatorCategory, monthly: list<float>, achievement: float}>
     */
    public function annualPerIndicator(Nurse $nurse, int $year): array
    {
        $indicators = $this->indicatorsFor($nurse->unit)
            ->with('category')
            ->get()
            ->sortBy('sort')
            ->values();

        $sums = [];

        foreach (DB::table('logbook_entries')
            ->where('nurse_id', $nurse->id)
            ->whereBetween('date', [
                Carbon::create($year, 1)->startOfMonth()->toDateString(),
                Carbon::create($year, 12)->endOfMonth()->toDateString(),
            ])
            ->selectRaw('indicator_id, substr(date, 6, 2) as month, SUM(value) as total')
            ->groupBy('indicator_id', 'month')
            ->get() as $row) {
            $sums[$row->indicator_id.'-'.$row->month] = (float) $row->total;
        }

        $workingDaysByMonth = [];

        foreach (MonthlyConfig::query()
            ->where('nurse_id', $nurse->id)
            ->where('year', $year)
            ->get() as $config) {
            $workingDaysByMonth[$config->month] = (int) $config->working_days;
        }

        $rows = [];

        foreach ($indicators as $indicator) {
            $monthly = [];

            for ($month = 1; $month <= 12; $month++) {
                $workingDays = $workingDaysByMonth[$month] ?? Carbon::create($year, $month)->daysInMonth;
                $monthly[] = $workingDays > 0 ? ($sums[$indicator->id.'-'.$month] ?? 0.0) / $workingDays : 0.0;
            }

            $rows[] = [
                'indicator' => $indicator,
                'category' => $indicator->category,
                'monthly' => $monthly,
                'achievement' => array_sum($monthly) / 12,
            ];
        }

        return $rows;
    }

    /**
     * Weighted annual total score per nurse, computed in bulk to avoid N+1.
     *
     * Mirrors the live computation of assessmentRows: each indicator's
     * achievement is the mean monthly achievement, multiplied by its category
     * weight.
     *
     * @param  iterable<Nurse>  $nurses
     * @return array<int, float> keyed by nurse id
     */
    public function annualScoresByNurses(iterable $nurses, int $year): array
    {
        $byNurse = [];

        foreach ($nurses as $nurse) {
            $byNurse[$nurse->id] = $nurse->unit_id;
        }

        if ($byNurse === []) {
            return [];
        }

        $nurseIds = array_keys($byNurse);
        $unitIds = array_values(array_unique(array_filter($byNurse)));

        $indicators = Indicator::query()
            ->where(fn ($query) => $query
                ->whereNull('unit_id')
                ->when($unitIds !== [], fn ($query) => $query->orWhereIn('unit_id', $unitIds)))
            ->with('category')
            ->get();

        $start = Carbon::create($year, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, 12)->endOfMonth()->toDateString();

        $sums = [];

        foreach (DB::table('logbook_entries')
            ->whereIn('nurse_id', $nurseIds)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('nurse_id, indicator_id, SUBSTR(date, 6, 2) as month, SUM(value) as total')
            ->groupBy('nurse_id', 'indicator_id', 'month')
            ->get() as $row) {
            $sums[$row->nurse_id.'-'.$row->indicator_id.'-'.$row->month] = (float) $row->total;
        }

        $workingDaysByMonth = [];

        foreach (MonthlyConfig::query()
            ->whereIn('nurse_id', $nurseIds)
            ->where('year', $year)
            ->get() as $config) {
            $workingDaysByMonth[$config->nurse_id.'-'.$config->month] = (int) $config->working_days;
        }

        $scores = [];

        foreach ($byNurse as $nurseId => $unitId) {
            $score = 0.0;

            foreach ($indicators as $indicator) {
                if ($indicator->unit_id !== null && $indicator->unit_id !== $unitId) {
                    continue;
                }

                $monthly = [];

                for ($month = 1; $month <= 12; $month++) {
                    $workingDays = $workingDaysByMonth[$nurseId.'-'.$month] ?? Carbon::create($year, $month)->daysInMonth;
                    $monthly[] = $workingDays > 0 ? ($sums[$nurseId.'-'.$indicator->id.'-'.$month] ?? 0.0) / $workingDays : 0.0;
                }

                $achievement = array_sum($monthly) / 12;
                $score += $achievement * $indicator->category->weightAsDecimal();
            }

            $scores[$nurseId] = $score;
        }

        return $scores;
    }

    /**
     * Annual assessment rows: snapshot of annual achievement per indicator with
     * its category weight (decimal) and resulting score.
     *
     * @return list<array{category: IndicatorCategory, weight_percent: int, rows: list<array{indicator: Indicator, target: string, achievement: float, weight: float, score: float}>}>
     */
    public function assessmentRows(Nurse $nurse, int $year): array
    {
        $groups = [];

        foreach ($this->annualPerIndicator($nurse, $year) as $row) {
            $categoryId = $row['category']->id;
            $groups[$categoryId]['category'] = $row['category'];
            $groups[$categoryId]['weight_percent'] = $row['category']->weight_percent;
            $groups[$categoryId]['rows'][] = [
                'indicator' => $row['indicator'],
                'target' => $row['indicator']->target,
                'achievement' => $row['achievement'],
                'weight' => $row['category']->weightAsDecimal(),
                'score' => $row['achievement'] * $row['category']->weightAsDecimal(),
            ];
        }

        return array_map(
            fn (array $group) => [
                'category' => $group['category'],
                'weight_percent' => $group['weight_percent'],
                'rows' => $group['rows'],
            ],
            array_values($groups)
        );
    }

    /**
     * The indicators that apply to a nurse: global ones plus the unit-specific ones.
     *
     * @return Builder<Indicator>
     */
    public function indicatorsFor(?Unit $unit): Builder
    {
        return Indicator::query()
            ->where(fn ($query) => $query
                ->whereNull('unit_id')
                ->when($unit, fn ($query) => $query->orWhere('unit_id', $unit->id)));
    }

    /**
     * Resolve the configured working days for a nurse/month, defaulting to the
     * calendar length of the month when no config exists yet.
     */
    public function workingDays(Nurse $nurse, int $month, int $year): int
    {
        $config = MonthlyConfig::query()
            ->where('nurse_id', $nurse->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($config === null) {
            return Carbon::create($year, $month)->daysInMonth;
        }

        return $config->working_days;
    }
}
