<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresMasterAccess;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    use RequiresMasterAccess;

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
}
