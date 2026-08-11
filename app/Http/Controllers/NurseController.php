<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Nurse;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NurseController extends Controller
{
    use RequiresMasterAccess;

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
