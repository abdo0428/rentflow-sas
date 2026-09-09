<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyFilterRequest;
use App\Http\Requests\SaveBuildingRequest;
use App\Models\Building;
use App\Models\ManagementCompany;
use App\Services\DeletePropertyRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(PropertyFilterRequest $request): View
    {
        Gate::authorize('viewAny', Building::class);
        $filters = $request->validated();
        $search = $filters['q'] ?? '';
        $buildings = Building::query()->withCount(['units', 'units as occupied_units_count' => fn (Builder $query) => $query->where('status', 'occupied')])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%');
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->latest('id')->paginate(10)->withQueryString();

        return view('buildings.index', [
            'buildings' => $buildings, 'filters' => $filters,
            'cities' => Building::query()->distinct()->orderBy('city')->pluck('city', 'city'),
            'total' => Building::count(), 'active' => Building::where('status', 'active')->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Building::class);

        return $this->form(new Building(['status' => 'active', 'total_floors' => 1]));
    }

    public function store(SaveBuildingRequest $request): RedirectResponse
    {
        Gate::authorize('create', Building::class);
        try {
            $building = Building::create([...$request->validated(), 'company_id' => $request->companyId()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('app.code')])]);
        }

        return to_route('buildings.show', $building)->with('success', __('property.building_created'));
    }

    public function show(PropertyFilterRequest $request, Building $building): View
    {
        Gate::authorize('view', $building);
        $building->loadCount(['units', 'units as occupied_units_count' => fn (Builder $query) => $query->where('status', 'occupied'),
            'units as vacant_units_count' => fn (Builder $query) => $query->where('status', 'vacant')]);

        return view('buildings.show', [
            'building' => $building,
            'units' => $building->units()->latest('id')->paginate(10, ['*'], 'units_page')->withQueryString(),
            'deletionReason' => $building->deletionBlockReason(),
        ]);
    }

    public function edit(Building $building): View
    {
        Gate::authorize('update', $building);

        return $this->form($building);
    }

    public function update(SaveBuildingRequest $request, Building $building): RedirectResponse
    {
        Gate::authorize('update', $building);
        try {
            DB::transaction(function () use ($request, $building) {
                $locked = Building::lockForUpdate()->findOrFail($building->id);
                if ($locked->units()->where('floor', '>', $request->integer('total_floors'))->exists()) {
                    throw ValidationException::withMessages(['total_floors' => __('property.floors_in_use')]);
                }
                $locked->update($request->safe()->except('company_id'));
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('app.code')])]);
        }

        return to_route('buildings.show', $building)->with('success', __('property.building_updated'));
    }

    public function destroy(Building $building, DeletePropertyRecord $delete): RedirectResponse
    {
        Gate::authorize('delete', $building);
        $delete->handle($building);

        return to_route('buildings.index')->with('success', __('property.building_deleted'));
    }

    private function form(Building $building): View
    {
        return view('buildings.form', [
            'building' => $building,
            'companies' => auth()->user()->hasRole('super_admin') && ! $building->exists
                ? ManagementCompany::where('status', 'active')->orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }
}
