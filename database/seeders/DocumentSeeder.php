<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            $lease = LeaseContract::where('company_id', $company->id)->firstOrFail();
            Storage::disk('local')->put('demo/lease-information.txt', 'RentFlow training document. This is sample lease information, not a signed contract.');
            Document::firstOrCreate(['company_id' => $company->id, 'title' => 'Demo lease information'], ['documentable_type' => $lease->getMorphClass(), 'documentable_id' => $lease->id, 'file_path' => 'demo/lease-information.txt', 'document_type' => 'lease', 'uploaded_by' => User::where('email', 'admin@example.com')->firstOrFail()->id]);
        });
    }
}
