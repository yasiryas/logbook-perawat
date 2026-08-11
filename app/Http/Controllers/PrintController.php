<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesNurseAccess;
use App\Models\AnnualAssessment;
use App\Models\Nurse;
use App\Services\LogbookCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends Controller
{
    use ResolvesNurseAccess;

    public function __construct(private readonly LogbookCalculationService $logbook) {}

    public function logbook(Request $request, Nurse $nurse): View
    {
        $nurse = $this->resolveNurse($nurse);

        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $grid = $this->logbook->grid($nurse, $month, $year);
        $recap = $this->logbook->monthlyRecap($nurse, $month, $year);

        return view('print.logbook', [
            'nurse' => $nurse,
            'month' => $month,
            'year' => $year,
            'workingDays' => $recap['working_days'],
            'daysInMonth' => $grid['days_in_month'],
            'gridCategories' => $grid['categories'],
            'categories' => $recap['categories'],
            'total' => $recap['total'],
        ]);
    }

    public function annual(Request $request, Nurse $nurse): View
    {
        $nurse = $this->resolveNurse($nurse);
        $year = (int) $request->integer('year', now()->year);

        $rows = $this->logbook->annualPerIndicator($nurse, $year);

        $categories = [];

        foreach ($rows as $row) {
            $categories[$row['category']->id][] = $row;
        }

        return view('print.annual', [
            'nurse' => $nurse,
            'year' => $year,
            'categories' => $categories,
            'total' => (float) array_sum(array_map(
                fn ($row) => $row['achievement'] * $row['category']->weightAsDecimal(),
                $rows
            )),
        ]);
    }

    public function assessment(AnnualAssessment $assessment): View
    {
        $this->resolveNurse($assessment->nurse);

        return view('print.assessment', [
            'assessment' => $assessment->load(['nurse.unit', 'items.indicator.category']),
        ]);
    }
}
