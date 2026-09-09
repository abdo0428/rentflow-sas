<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeletePropertyRecord
{
    public function handle(Building|Unit|Tenant $record): void
    {
        try {
            DB::transaction(function () use ($record) {
                $locked = $record->newQuery()->lockForUpdate()->findOrFail($record->id);
                Gate::authorize('delete', $locked);
                if ($reason = $locked->deletionBlockReason()) {
                    throw ValidationException::withMessages(['deletion' => __($reason)]);
                }
                $locked->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1451) {
                throw $exception;
            }
            throw ValidationException::withMessages(['deletion' => __('property.related_records')]);
        }
    }
}
