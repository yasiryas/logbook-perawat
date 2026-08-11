<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicatorController extends Controller
{
    use RequiresMasterAccess;

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
