<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignMaintenanceRequest;
use App\Http\Requests\StoreMaintenanceNoteRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\TransitionMaintenanceRequest;
use App\Http\Requests\WorkflowFilterRequest;
use App\Models\Building;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\MaintenanceWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceRequestController extends Controller
{
    public function __construct(private MaintenanceWorkflowService $workflow) {}

    public function index(WorkflowFilterRequest $request): View
    {
        Gate::authorize('viewAny', MaintenanceRequest::class);
        $filters = $request->validated();
        $query = $this->displayQuery();
        foreach (['status', 'priority', 'building_id', 'assigned_to'] as $field) {
            $query->when($filters[$field] ?? null, fn ($query, $value) => $query->where($field, $value));
        }
        $counts = MaintenanceRequest::selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $buildingOptions = auth()->user()->hasRole('maintenance_staff')
            ? DB::table('buildings')->whereIn('id', MaintenanceRequest::select('building_id'))->orderBy('name')->pluck('name', 'id')
            : Building::orderBy('name')->pluck('name', 'id');

        return view(auth()->user()->hasRole('tenant') ? 'tenant.maintenance' : 'maintenance.index', [
            'requests' => $query->latest('id')->paginate(10)->withQueryString(), 'filters' => $filters,
            'counts' => $counts, 'buildings' => $buildingOptions, 'staff' => $this->staffOptions()->pluck('name', 'id'),
            'openCount' => $counts->only(['new', 'under_review', 'assigned', 'in_progress'])->sum(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceRequest::class);

        return view('maintenance.create', [
            'tenants' => Tenant::whereHas('leases', fn ($query) => $query->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today()))->orderBy('full_name')->pluck('full_name', 'id'),
            'units' => Unit::with('building')->whereHas('leases', fn ($query) => $query->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today()))
                ->get()->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->building?->name.' · '.$unit->unit_number]),
        ]);
    }

    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $maintenanceRequest = $this->workflow->create($request->validated());

        return to_route('maintenance.show', $maintenanceRequest)->with('success', __('workflow.request_created'));
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('view', $maintenanceRequest);
        $maintenanceRequest = $this->displayQuery()->findOrFail($maintenanceRequest->id);
        $canNote = Gate::allows('note', $maintenanceRequest);

        return view('maintenance.show', [
            'maintenanceRequest' => $maintenanceRequest,
            'timeline' => $maintenanceRequest->activities()->where('kind', 'status')->with('user')->oldest('id')->paginate(15, ['*'], 'timeline_page')->withQueryString(),
            'notes' => $canNote ? $maintenanceRequest->activities()->where('kind', 'note')->with('user')->latest('id')->paginate(10, ['*'], 'notes_page')->withQueryString() : null,
            'transitions' => $this->workflow->availableTransitions($maintenanceRequest),
            'staff' => Gate::allows('assign', $maintenanceRequest) ? $this->staffOptions($maintenanceRequest->company_id)->pluck('name', 'id') : collect(),
        ]);
    }

    public function assign(AssignMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->workflow->assign($maintenanceRequest, $request->integer('assigned_to'));

        return back()->with('success', __('workflow.staff_assigned'));
    }

    public function photo(MaintenanceRequest $maintenanceRequest): StreamedResponse
    {
        Gate::authorize('view', $maintenanceRequest);
        abort_unless($maintenanceRequest->photo_path && Storage::disk('local')->exists($maintenanceRequest->photo_path), 404);

        return Storage::disk('local')->download($maintenanceRequest->photo_path);
    }

    public function transition(TransitionMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->workflow->transition($maintenanceRequest, $request->validated('status'));

        return back()->with('success', __('workflow.status_updated'));
    }

    public function note(StoreMaintenanceNoteRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->workflow->addNote($maintenanceRequest, $request->validated('body'));

        return back()->with('success', __('workflow.note_saved'));
    }

    private function staffOptions(?int $companyId = null): Builder
    {
        return User::where('status', 'active')->role('maintenance_staff')
            ->when($companyId ?? auth()->user()->company_id, fn ($query, $id) => $query->where('company_id', $id))
            ->when(auth()->user()->hasRole('maintenance_staff'), fn ($query) => $query->whereKey(auth()->id()))
            ->when(auth()->user()->hasRole('tenant'), fn ($query) => $query->whereIn('id', MaintenanceRequest::select('assigned_to')->whereNotNull('assigned_to')))
            ->orderBy('name');
    }

    private function displayQuery(): Builder
    {
        return MaintenanceRequest::with('assignee')->select('maintenance_requests.*')->addSelect([
            'building_label' => DB::table('buildings')->select('name')->whereColumn('buildings.id', 'maintenance_requests.building_id')->whereColumn('buildings.company_id', 'maintenance_requests.company_id')->limit(1),
            'unit_label' => DB::table('units')->select('unit_number')->whereColumn('units.id', 'maintenance_requests.unit_id')->whereColumn('units.company_id', 'maintenance_requests.company_id')->limit(1),
        ]);
    }
}
