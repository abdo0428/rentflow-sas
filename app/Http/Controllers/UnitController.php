<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyFilterRequest;
use App\Http\Requests\SaveUnitRequest;
use App\Models\Building;
use App\Models\Unit;
use App\Services\DeletePropertyRecord;
use App\Services\SaveUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(private SaveUnit $saveUnit) {}

    public function index(PropertyFilterRequest $request): View
    {
        Gate::authorize('viewAny', Unit::class);
        $filters = $request->validated();
        $search = $filters['q'] ?? '';
        $units = Unit::with('building')
            ->when($filters['building_id'] ?? null, fn (Builder $query, int $id) => $query->where('building_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where('unit_number', 'like', '%'.$search.'%'))
            ->latest('id')->paginate(10)->withQueryString();
        $counts = Unit::selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return view('units.index', [
            'units' => $units, 'filters' => $filters, 'counts' => $counts, 'total' => $counts->sum(),
            'buildings' => Building::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(PropertyFilterRequest $request): View
    {
        Gate::authorize('create', Unit::class);

        return $this->form(new Unit(['building_id' => $request->validated('building_id'), 'status' => 'vacant', 'type' => 'apartment', 'floor' => 1]));
    }

    public function store(SaveUnitRequest $request): RedirectResponse
    {
        Gate::authorize('create', Unit::class);
        $unit = $this->saveUnit->handle($request->validated(), new Unit);

        return to_route('units.show', $unit)->with('success', __('property.unit_created'));
    }

    public function show(Unit $unit): View
    {
        Gate::authorize('view', $unit);
        $unit->load('building');

        return view('units.show', ['unit' => $unit, 'deletionReason' => $unit->deletionBlockReason()]);
    }

    public function edit(Unit $unit): View
    {
        Gate::authorize('update', $unit);

        return $this->form($unit);
    }

    public function update(SaveUnitRequest $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('update', $unit);
        $unit = $this->saveUnit->handle($request->validated(), $unit);

        return to_route('units.show', $unit)->with('success', __('property.unit_updated'));
    }

    public function destroy(Unit $unit, DeletePropertyRecord $delete): RedirectResponse
    {
        Gate::authorize('delete', $unit);
        $delete->handle($unit);

        return to_route('units.index')->with('success', __('property.unit_deleted'));
    }

    private function form(Unit $unit): View
    {
        return view('units.form', [
            'unit' => $unit,
            'buildings' => Building::query()->when($unit->exists, fn (Builder $query) => $query->where('company_id', $unit->company_id))->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
