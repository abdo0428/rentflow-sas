<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Unit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveUnit
{
    public function handle(array $data, Unit $unit): Unit
    {
        try {
            return DB::transaction(function () use ($data, $unit) {
                $building = Building::query()->lockForUpdate()->findOrFail((int) $data['building_id']);
                if ($unit->exists) {
                    $unit = Unit::query()->lockForUpdate()->findOrFail($unit->id);
                    abort_unless((int) $building->company_id === (int) $unit->company_id, 403);
                    if ((int) $building->id !== (int) $unit->building_id && $unit->deletionBlockReason()) {
                        throw ValidationException::withMessages(['building_id' => __('property.unit_move_locked')]);
                    }
                    if ($unit->leases()->where('status', 'active')->exists() && $data['status'] !== 'occupied') {
                        throw ValidationException::withMessages(['status' => __('property.unit_active_lease')]);
                    }
                }
                if ((int) $data['floor'] > $building->total_floors) {
                    throw ValidationException::withMessages(['floor' => __('property.floor_exceeds_building')]);
                }
                $unit->fill($data);
                $unit->company_id = $building->company_id;
                $unit->save();

                return $unit;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['unit_number' => __('validation.unique', ['attribute' => __('app.unit_number')])]);
        }
    }
}
