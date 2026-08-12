<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Unit;
use App\Services\MasterDataExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitController extends Controller
{
    use RequiresMasterAccess;

    public function __construct(private readonly MasterDataExcelService $excel) {}

    public function index(): Response
    {
        $this->authorizeMaster();

        $units = Unit::withCount(['nurses', 'indicators'])
            ->orderBy('name')
            ->get();

        return Inertia::render('units/Index', ['units' => $units]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        Unit::create($data);

        return redirect()->route('units.index')->with('flash', ['success' => 'Unit ditambahkan.']);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $unit->update($data);

        return redirect()->route('units.index')->with('flash', ['success' => 'Unit diperbarui.']);
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorizeMaster();

        abort_if($unit->nurses()->exists(), 422, 'Unit masih memiliki perawat.');

        $unit->delete();

        return redirect()->route('units.index')->with('flash', ['success' => 'Unit dihapus.']);
    }

    public function export(): StreamedResponse
    {
        $this->authorizeMaster();

        return $this->excel->export('units');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            $result = $this->excel->import('units', $data['file']->getRealPath());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        Inertia::flash('toast', $this->flashMessage('Unit', $result));

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
}
