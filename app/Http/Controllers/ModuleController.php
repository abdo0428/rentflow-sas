<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\RentPayment;
use App\Models\Unit;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModuleController extends Controller
{
    public function index(Request $request): View
    {
        $module = $request->route('module');
        $definition = Navigation::MODULES[$module];
        Gate::authorize('viewAny', $definition['model']);

        return view('modules.index', [
            'module' => $module,
            'columns' => $definition['columns'],
            'records' => $definition['model']::query()->latest('id')->paginate(10),
        ]);
    }

    public function show(Request $request, string $record): View
    {
        $module = $request->route('module');
        $definition = Navigation::MODULES[$module];
        $model = $definition['model']::query()->findOrFail($record);
        Gate::authorize('view', $model);
        $columns = $definition['columns'];
        if ($module === 'maintenance') {
            $columns = [...$columns, 'description', 'completed_at'];
        } elseif ($module === 'announcements') {
            $columns[] = 'body';
        }

        return view('modules.show', ['module' => $module, 'record' => $model, 'columns' => $columns]);
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path);
    }

    public function reports(): View
    {
        Gate::authorize('reports.view');

        return view('modules.reports', [
            'collected' => RentPayment::where('status', 'paid')->sum('amount'),
            'outstanding' => RentPayment::whereIn('status', ['pending', 'overdue'])->sum('amount'),
            'occupied' => Unit::where('status', 'occupied')->count(),
            'total' => Unit::count(),
        ]);
    }

    public function settings(Request $request): View
    {
        Gate::authorize('settings.view');

        return view('modules.settings', ['user' => $request->user(), 'company' => $request->user()->company]);
    }
}
