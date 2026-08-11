<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesNurseAccess;
use App\Models\MonthlyConfig;
use App\Models\Nurse;
use App\Services\LogbookCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyLogbookController extends Controller
{
    use ResolvesNurseAccess;

    public function __construct(private readonly LogbookCalculationService $logbook) {}

    public function edit(Request $request, ?Nurse $nurse = null): Response
    {
        $nurse = $nurse ? $this->resolveNurse($nurse) : $this->defaultNurse();
        abort_if(! $nurse, 404, 'Belum ada profil perawat');

        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $grid = $this->logbook->grid($nurse, $month, $year);
        $config = MonthlyConfig::firstOrCreate(
            ['nurse_id' => $nurse->id, 'month' => $month, 'year' => $year],
            ['working_days' => $grid['days_in_month']]
        );

        return Inertia::render('logbook/Edit', [
            'nurse' => $nurse->load('unit'),
            'nurses' => $this->nurseOptions(),
            'month' => $month,
            'year' => $year,
            'workingDays' => $config->working_days,
            'daysInMonth' => $grid['days_in_month'],
            'categories' => $this->prepareCategories($grid['categories']),
        ]);
    }

    public function update(Request $request, ?Nurse $nurse = null): RedirectResponse
    {
        $nurse = $nurse ? $this->resolveNurse($nurse) : $this->defaultNurse();
        abort_if(! $nurse, 404, 'Belum ada profil perawat');

        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'working_days' => ['required', 'integer', 'min:1', 'max:31'],
            'values' => ['array'],
        ]);

        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $workingDays = (int) $data['working_days'];

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $workingDays = min($workingDays, $daysInMonth);

        MonthlyConfig::updateOrCreate(
            ['nurse_id' => $nurse->id, 'month' => $month, 'year' => $year],
            ['working_days' => $workingDays]
        );

        $allowedIndicatorIds = $this->logbook->indicatorsFor($nurse->unit)
            ->pluck('id')
            ->all();

        $saved = 0;
        foreach (($data['values'] ?? []) as $indicatorId => $days) {
            if (! in_array((int) $indicatorId, $allowedIndicatorIds, true)) {
                continue;
            }

            foreach ($days as $day => $value) {
                $day = (int) $day;
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                if (! checkdate($month, $day, $year)) {
                    continue;
                }

                if ($value === null || $value === '') {
                    $nurse->entries()
                        ->where('indicator_id', $indicatorId)
                        ->where('date', $date)
                        ->delete();

                    continue;
                }

                $nurse->entries()->updateOrCreate(
                    ['indicator_id' => $indicatorId, 'date' => $date],
                    ['value' => (float) $value]
                );
                $saved++;
            }
        }

        return back()->with('flash', ['success' => "Logbook {$month}/{$year} disimpan ($saved nilai)."]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function prepareCategories(array $categories): array
    {
        $result = [];

        foreach ($categories as $row) {
            $indicatorRows = [];

            foreach ($row['indicators'] as $item) {
                $indicatorRows[] = [
                    'id' => $item['indicator']->id,
                    'sort' => $item['indicator']->sort,
                    'name' => $item['indicator']->name,
                    'target' => $item['indicator']->target,
                    'is_checkbox' => $item['indicator']->isCheckbox(),
                    'values' => $item['values'],
                ];
            }

            $result[] = [
                'id' => $row['category']->id,
                'code' => $row['category']->code,
                'name' => $row['category']->name,
                'weight_percent' => $row['category']->weight_percent,
                'indicators' => $indicatorRows,
            ];
        }

        return $result;
    }
}
