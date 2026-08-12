<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Nurse;
use App\Models\Unit;
use App\Models\User;
use App\Services\MasterDataExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NurseController extends Controller
{
    use RequiresMasterAccess;

    public function __construct(private readonly MasterDataExcelService $excel) {}

    public function index(Request $request): Response
    {
        $this->authorizeMaster();

        $unitSlug = $request->query('unit');

        $nurses = Nurse::query()
            ->with('unit')
            ->when($unitSlug, fn ($query) => $query->whereHas('unit', fn ($query) => $query->where('slug', $unitSlug)))
            ->orderBy('name')
            ->get();

        return Inertia::render('nurses/Index', [
            ...$this->formProps(),
            'nurses' => $nurses,
        ]);
    }

    public function create(): Response
    {
        $this->authorizeMaster();

        return Inertia::render('nurses/Edit', $this->formProps());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $this->validateNurse($request);

        Nurse::create($data);

        return redirect()->route('nurses.index')->with('flash', ['success' => 'Perawat ditambahkan.']);
    }

    public function edit(Nurse $nurse): Response
    {
        $this->authorizeMaster();

        return Inertia::render('nurses/Edit', [
            ...$this->formProps(),
            'nurse' => $nurse,
        ]);
    }

    public function update(Request $request, Nurse $nurse): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $this->validateNurse($request, $nurse);

        $nurse->update($data);

        return redirect()->route('nurses.index')->with('flash', ['success' => 'Perawat diperbarui.']);
    }

    public function destroy(Nurse $nurse): RedirectResponse
    {
        $this->authorizeMaster();

        abort_if($nurse->entries()->exists(), 422, 'Perawat masih memiliki entry logbook.');

        $nurse->delete();

        return redirect()->route('nurses.index')->with('flash', ['success' => 'Perawat dihapus.']);
    }

    public function export(): StreamedResponse
    {
        $this->authorizeMaster();

        return $this->excel->export('nurses');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            $result = $this->excel->import('nurses', $data['file']->getRealPath());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        Inertia::flash('toast', $this->flashMessage('Perawat', $result));

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
     * @return array{units: mixed, users: mixed}
     */
    private function formProps(): array
    {
        return [
            'units' => Unit::orderBy('name')->get(['id', 'name', 'slug']),
            'users' => User::query()
                ->whereDoesntHave('nurse')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateNurse(Request $request, ?Nurse $nurse = null): array
    {
        $nipRule = 'required|string|max:255|unique:nurses,nip';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => $nurse ? $nipRule.','.$nurse->id : $nipRule,
            'pk' => ['required', 'string', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
