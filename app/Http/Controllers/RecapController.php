<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesNurseAccess;
use App\Models\Nurse;
use App\Services\LogbookCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecapController extends Controller
{
    use ResolvesNurseAccess;

    public function __construct(private readonly LogbookCalculationService $logbook) {}

    public function monthly(Request $request, ?Nurse $nurse = null): Response
    {
        $nurse = $nurse ? $this->resolveNurse($nurse) : $this->defaultNurse();
        abort_if(! $nurse, 404, 'Belum ada profil perawat');

        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $recap = $this->logbook->monthlyRecap($nurse, $month, $year);

        return Inertia::render('recaps/Monthly', [
            'nurse' => $nurse->load('unit'),
            'nurses' => $this->nurseOptions(),
            'month' => $month,
            'year' => $year,
            'workingDays' => $recap['working_days'],
            'categories' => $this->prepareCategories($recap['categories']),
            'total' => $recap['total'],
        ]);
    }

    public function annual(Request $request, ?Nurse $nurse = null): Response
    {
        $nurse = $nurse ? $this->resolveNurse($nurse) : $this->defaultNurse();
        abort_if(! $nurse, 404, 'Belum ada profil perawat');

        $year = (int) $request->integer('year', now()->year);

        $rows = $this->logbook->annualPerIndicator($nurse, $year);

        return Inertia::render('recaps/Annual', [
            'nurse' => $nurse->load('unit'),
            'nurses' => $this->nurseOptions(),
            'year' => $year,
            'categories' => $this->prepareAnnualCategories($rows),
            'total' => (float) array_sum(array_map(
                fn ($row) => $row['achievement'] * $row['category']->weightAsDecimal(),
                $rows
            )),
        ]);
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
                    'values' => $item['values'],
                    'sum' => $item['sum'],
                    'filled_days' => $item['filled_days'],
                    'achievement' => $item['achievement'],
                ];
            }

            $result[] = [
                'id' => $row['category']->id,
                'code' => $row['category']->code,
                'name' => $row['category']->name,
                'weight_percent' => $row['weight_percent'],
                'achievement' => $row['achievement'],
                'indicators' => $indicatorRows,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareAnnualCategories(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $categoryId = $row['category']->id;
            $groups[$categoryId]['id'] = $row['category']->id;
            $groups[$categoryId]['code'] = $row['category']->code;
            $groups[$categoryId]['name'] = $row['category']->name;
            $groups[$categoryId]['weight_percent'] = $row['category']->weight_percent;
            $groups[$categoryId]['indicators'][] = [
                'id' => $row['indicator']->id,
                'sort' => $row['indicator']->sort,
                'name' => $row['indicator']->name,
                'target' => $row['indicator']->target,
                'monthly' => $row['monthly'],
                'achievement' => $row['achievement'],
            ];
        }

        return array_values($groups);
    }
}
