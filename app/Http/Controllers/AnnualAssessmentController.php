<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Http\Controllers\Concerns\ResolvesNurseAccess;
use App\Models\AnnualAssessment;
use App\Models\AnnualAssessmentItem;
use App\Models\Nurse;
use App\Services\LogbookCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnualAssessmentController extends Controller
{
    use ResolvesNurseAccess;

    public function __construct(private readonly LogbookCalculationService $logbook) {}

    public function index(Request $request): Response
    {
        $year = (int) $request->integer('year', now()->year);

        $nurses = $this->visibleNurses()
            ->with(['unit', 'annualAssessments' => fn ($query) => $query->where('year', $year)])
            ->orderBy('name')
            ->get();

        $draftScores = $this->logbook->annualScoresByNurses(
            $nurses->reject(fn (Nurse $nurse) => $nurse->annualAssessments->first()?->isFinal() ?? false),
            $year
        );

        $rows = $nurses->map(function (Nurse $nurse) use ($year, $draftScores) {
            $assessment = $nurse->annualAssessments->first();
            $total = $assessment && $assessment->isFinal()
                ? $assessment->totalScore()
                : (float) ($draftScores[$nurse->id] ?? 0.0);

            return [
                'id' => $nurse->id,
                'name' => $nurse->name,
                'nip' => $nurse->nip,
                'pk' => $nurse->pk,
                'unit' => $nurse->unit->name,
                'year' => $year,
                'status' => $assessment?->status->value ?? AssessmentStatus::Draft->value,
                'total' => round($total, 6),
            ];
        });

        return Inertia::render('assessments/Index', [
            'year' => $year,
            'nurses' => $rows,
        ]);
    }

    public function show(Request $request, Nurse $nurse): Response
    {
        $nurse = $this->resolveNurse($nurse);
        $year = (int) $request->integer('year', now()->year);

        $assessment = AnnualAssessment::firstOrCreate(
            ['nurse_id' => $nurse->id, 'year' => $year],
            ['status' => AssessmentStatus::Draft]
        );

        if ($assessment->items()->count() === 0) {
            foreach ($this->logbook->assessmentRows($nurse, $year) as $group) {
                foreach ($group['rows'] as $row) {
                    $assessment->items()->updateOrCreate(
                        ['indicator_id' => $row['indicator']->id],
                        [
                            'target' => $row['target'],
                            'achievement' => $row['achievement'],
                            'weight' => $row['weight'],
                            'score' => $row['score'],
                        ]
                    );
                }
            }
        }

        return Inertia::render('assessments/Show', [
            'nurse' => $nurse->load('unit'),
            'year' => $year,
            'assessment' => [
                'id' => $assessment->id,
                'status' => $assessment->status->value,
                'assessor_name' => $assessment->assessor_name,
                'assessor_nip' => $assessment->assessor_nip,
                'assessor_position' => $assessment->assessor_position,
                'assessor_unit' => $assessment->assessor_unit,
                'superior_name' => $assessment->superior_name,
                'superior_nip' => $assessment->superior_nip,
                'superior_position' => $assessment->superior_position,
                'superior_unit' => $assessment->superior_unit,
            ],
            'categories' => $this->prepareCategories($assessment),
        ]);
    }

    public function update(Request $request, Nurse $nurse): RedirectResponse
    {
        $nurse = $this->resolveNurse($nurse);
        $year = (int) $request->integer('year', now()->year);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'assessor_name' => ['nullable', 'string', 'max:255'],
            'assessor_nip' => ['nullable', 'string', 'max:255'],
            'assessor_position' => ['nullable', 'string', 'max:255'],
            'assessor_unit' => ['nullable', 'string', 'max:255'],
            'superior_name' => ['nullable', 'string', 'max:255'],
            'superior_nip' => ['nullable', 'string', 'max:255'],
            'superior_position' => ['nullable', 'string', 'max:255'],
            'superior_unit' => ['nullable', 'string', 'max:255'],
            'items' => ['array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.achievement' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assessment = AnnualAssessment::updateOrCreate(
            ['nurse_id' => $nurse->id, 'year' => $year],
            [
                'assessor_name' => $data['assessor_name'] ?? null,
                'assessor_nip' => $data['assessor_nip'] ?? null,
                'assessor_position' => $data['assessor_position'] ?? null,
                'assessor_unit' => $data['assessor_unit'] ?? null,
                'superior_name' => $data['superior_name'] ?? null,
                'superior_nip' => $data['superior_nip'] ?? null,
                'superior_position' => $data['superior_position'] ?? null,
                'superior_unit' => $data['superior_unit'] ?? null,
            ]
        );

        foreach (($data['items'] ?? []) as $item) {
            $itemModel = $assessment->items()->find((int) $item['id']);
            if (! $itemModel) {
                continue;
            }

            $achievement = $item['achievement'] === null || $item['achievement'] === ''
                ? 0.0
                : (float) $item['achievement'];

            $itemModel->update([
                'achievement' => $achievement,
                'score' => $achievement * $itemModel->weight,
            ]);
        }

        return back()->with('flash', ['success' => 'Draf penilaian disimpan.']);
    }

    public function finalize(Request $request, Nurse $nurse): RedirectResponse
    {
        $nurse = $this->resolveNurse($nurse);
        $year = (int) $request->integer('year', now()->year);

        $assessment = AnnualAssessment::firstOrCreate(
            ['nurse_id' => $nurse->id, 'year' => $year],
            ['status' => AssessmentStatus::Draft]
        );

        if ($assessment->isFinal()) {
            return back()->with('flash', ['error' => 'Penilaian sudah final.']);
        }

        $assessment->update([
            'status' => AssessmentStatus::Final,
            'finalized_at' => now(),
        ]);

        return back()->with('flash', ['success' => 'Penilaian difinalisasi. Angka terkunci.']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function prepareCategories(AnnualAssessment $assessment): array
    {
        return $assessment->items()
            ->with(['indicator.category', 'indicator.unit'])
            ->orderBy('id')
            ->get()
            ->groupBy('indicator.category_id')
            ->map(function ($group) {
                $category = $group->first()->indicator->category;

                $rows = $group->map(function (AnnualAssessmentItem $item) {
                    return [
                        'id' => $item->id,
                        'indicator_id' => $item->indicator_id,
                        'name' => $item->indicator->name,
                        'target' => $item->target,
                        'achievement' => (float) $item->achievement,
                        'weight' => (float) $item->weight,
                        'score' => (float) $item->score,
                    ];
                })->values()->all();

                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'weight_percent' => $category->weight_percent,
                    'rows' => $rows,
                    'achievement' => (float) collect($rows)->avg('achievement'),
                ];
            })
            ->values()
            ->all();
    }
}
