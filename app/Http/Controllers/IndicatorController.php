<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Unit;
use App\Services\MasterDataExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorController extends Controller
{
    use RequiresMasterAccess;

    public function __construct(private readonly MasterDataExcelService $excel) {}

    public function index(Request $request): Response
    {
        $this->authorizeMaster();

        $unitId = $request->integer('unit') ?: null;

        $categories = IndicatorCategory::with(['indicators' => function ($query) use ($unitId) {
            $query->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
                ->orderBy('sort');
        }])
            ->orderBy('code')
            ->get();

        return Inertia::render('indicators/Index', [
            'categories' => $categories,
            'units' => Unit::orderBy('name')->get(['id', 'name']),
            'selectedUnit' => $unitId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $this->validateIndicator($request);

        $data['sort'] = (int) $data['sort'] ?: Indicator::query()
            ->where('category_id', $data['category_id'])
            ->when(isset($data['unit_id']), fn ($query) => $query->where('unit_id', $data['unit_id']))
            ->max('sort') + 1;

        Indicator::create($data);

        return back()->with('flash', ['success' => 'Indikator ditambahkan.']);
    }

    public function update(Request $request, Indicator $indicator): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $this->validateIndicator($request);

        $indicator->update($data);

        return back()->with('flash', ['success' => 'Indikator diperbarui.']);
    }

    public function destroy(Indicator $indicator): RedirectResponse
    {
        $this->authorizeMaster();

        abort_if($indicator->entries()->exists(), 422, 'Indikator masih dipakai di logbook.');

        $indicator->delete();

        return back()->with('flash', ['success' => 'Indikator dihapus.']);
    }

    public function export(): StreamedResponse
    {
        $this->authorizeMaster();

        return $this->excel->export('indicators');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            $result = $this->excel->import('indicators', $data['file']->getRealPath());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        Inertia::flash('toast', $this->flashMessage('Indikator', $result));

        return back();
    }

    /**
     * @param  array{imported: int, errors: array<int, string>}  $result
     * @return array{type: string, message: string}
     */
    private function flashMessage(string $label, array $result): array
    {
        $message = "{$result['imported']} {$label} berhasil diimpor.";

        if ($result['errors'] !== []) {
            $message .= "\n".implode("\n", array_slice($result['errors'], 0, 5));

            if (count($result['errors']) > 5) {
                $message .= "\n…dan ".(count($result['errors']) - 5).' baris lainnya.';
            }

            return ['type' => 'warning', 'message' => $message];
        }

        return ['type' => 'success', 'message' => $message];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIndicator(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:indicator_categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'target' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
