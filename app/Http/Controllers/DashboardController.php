<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesNurseAccess;
use App\Models\Nurse;
use App\Models\TeamInvitation;
use App\Services\LogbookCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesNurseAccess;

    public function __construct(private readonly LogbookCalculationService $logbook) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', $this->payload($request));
    }

    public function stats(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        return response()->json(array_filter($payload, fn (string $key) => in_array(
            $key,
            ['nurse', 'month', 'year', 'workingDays', 'daysInMonth', 'categories', 'total', 'annual', 'filledCells'],
            true
        ), ARRAY_FILTER_USE_KEY));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $nurse = $request->filled('nurse') && $request->integer('nurse') > 0
            ? $this->visibleNurses()->find((int) $request->integer('nurse'))
            : $this->defaultNurse();

        $month = max(1, min(12, (int) $request->integer('month', now()->month)));
        $year = (int) $request->integer('year', now()->year);

        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $payload = [
            'pendingInvitations' => $pendingInvitations,
            'nurse' => $nurse?->load('unit'),
            'nurses' => $this->nurseOptions(),
            'month' => $month,
            'year' => $year,
        ];

        if ($nurse) {
            $grid = $this->logbook->grid($nurse, $month, $year);
            $recap = $this->logbook->monthlyRecap($nurse, $month, $year, $grid);

            $annual = array_sum(array_map(
                fn (array $row) => $row['achievement'] * $row['category']->weightAsDecimal(),
                $this->logbook->annualPerIndicator($nurse, $year)
            ));

            $payload += [
                'workingDays' => $recap['working_days'],
                'daysInMonth' => $grid['days_in_month'],
                'categories' => $this->prepareCategories($recap['categories'], $grid['categories']),
                'total' => $recap['total'],
                'annual' => $annual,
                'filledCells' => collect($grid['categories'])
                    ->sum(fn (array $row) => collect($row['indicators'])->sum(
                        fn (array $item) => count($item['values'])
                    )),
            ];
        }

        return $payload;
    }

    /**
     * Merge the per-indicator recap stats into the grid, keeping the daily values.
     *
     * @param  array<int, array<string, mixed>>  $recapCategories
     * @param  array<int, array<string, mixed>>  $gridCategories
     * @return array<int, array<string, mixed>>
     */
    private function prepareCategories(array $recapCategories, array $gridCategories): array
    {
        $result = [];

        foreach ($recapCategories as $i => $row) {
            $gridRows = $gridCategories[$i]['indicators'] ?? [];

            $indicatorRows = [];

            foreach ($row['indicators'] as $j => $item) {
                $indicatorRows[] = [
                    'id' => $item['indicator']->id,
                    'sort' => $item['indicator']->sort,
                    'name' => $item['indicator']->name,
                    'target' => $item['indicator']->target,
                    'is_checkbox' => $item['indicator']->isCheckbox(),
                    'values' => $gridRows[$j]['values'] ?? [],
                    'sum' => $item['sum'],
                    'filled_days' => $item['filled_days'],
                    'achievement' => $item['achievement'],
                ];
            }

            $result[] = [
                'id' => $row['category']->id,
                'code' => $row['category']->code,
                'name' => $row['category']->name,
                'weight_percent' => $row['category']->weight_percent,
                'achievement' => $row['achievement'],
                'indicators' => $indicatorRows,
            ];
        }

        return $result;
    }
}