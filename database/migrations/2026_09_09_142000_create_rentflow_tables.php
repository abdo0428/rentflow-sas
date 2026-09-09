<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('management_companies')->restrictOnDelete();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unique(['company_id', 'id']);
        });
        Schema::create('buildings', function (Blueprint $table) {
            $this->companyColumns($table);
            $table->string('name');
            $table->string('code');
            $table->text('address');
            $table->string('city');
            $table->string('district');
            $table->unsignedSmallInteger('total_floors');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
        Schema::create('units', function (Blueprint $table) {
            $this->companyColumns($table);
            $this->companyForeign($table, 'building_id', 'buildings', false);
            $table->string('unit_number');
            $table->smallInteger('floor');
            $table->enum('type', ['apartment', 'office', 'shop', 'storage']);
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('rent_amount', 12, 2);
            $table->enum('status', ['vacant', 'occupied', 'maintenance', 'reserved'])->default('vacant');
            $table->unique(['building_id', 'unit_number']);
            $table->unique(['company_id', 'id', 'building_id'], 'units_building_reference');
            $table->index(['company_id', 'status']);
        });
        Schema::create('tenants', function (Blueprint $table) {
            $this->companyColumns($table);
            $this->companyForeign($table, 'user_id', 'users', true);
            $table->unique('user_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('national_id')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->index(['company_id', 'email']);
        });
        Schema::create('lease_contracts', function (Blueprint $table) {
            $this->companyColumns($table);
            $this->companyForeign($table, 'tenant_id', 'tenants', false);
            $this->companyForeign($table, 'unit_id', 'units', false);
            $table->string('contract_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('security_deposit', 12, 2)->default(0);
            $table->unsignedTinyInteger('payment_due_day')->default(1);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('draft');
            $table->string('contract_file')->nullable();
            $table->unique(['company_id', 'contract_number']);
            $table->unique(['company_id', 'id', 'tenant_id', 'unit_id'], 'leases_payment_reference');
            $table->index(['company_id', 'status', 'end_date']);
        });
        Schema::create('rent_payments', function (Blueprint $table) {
            $this->companyColumns($table);
            $table->unsignedBigInteger('lease_contract_id');
            $this->companyForeign($table, 'tenant_id', 'tenants', false);
            $this->companyForeign($table, 'unit_id', 'units', false);
            $table->foreign(['company_id', 'lease_contract_id', 'tenant_id', 'unit_id'], 'payments_lease_consistency')->references(['company_id', 'id', 'tenant_id', 'unit_id'])->on('lease_contracts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('receipt_file')->nullable();
            $table->index(['company_id', 'status', 'due_date']);
        });
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $this->companyColumns($table);
            $this->companyForeign($table, 'building_id', 'buildings', false);
            $table->unsignedBigInteger('unit_id');
            $table->foreign(['company_id', 'unit_id', 'building_id'], 'maintenance_unit_building')->references(['company_id', 'id', 'building_id'])->on('units')->restrictOnDelete();
            $this->companyForeign($table, 'tenant_id', 'tenants', false);
            $this->companyForeign($table, 'assigned_to', 'users', true);
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['new', 'under_review', 'assigned', 'in_progress', 'completed', 'cancelled', 'rejected'])->default('new');
            $table->date('preferred_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->index(['company_id', 'assigned_to', 'status'], 'maintenance_assignment_index');
        });
        Schema::create('announcements', function (Blueprint $table) {
            $this->companyColumns($table);
            $this->companyForeign($table, 'building_id', 'buildings', true);
            $table->string('title');
            $table->text('body');
            $table->enum('target', ['all', 'company', 'building'])->default('company');
            $table->timestamp('published_at')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->index(['company_id', 'status', 'published_at']);
        });
        Schema::create('documents', function (Blueprint $table) {
            $this->companyColumns($table);
            $table->morphs('documentable');
            $table->string('title');
            $table->string('file_path');
            $table->string('document_type');
            $this->companyForeign($table, 'uploaded_by', 'users', false);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('management_companies')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    private function companyColumns(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('company_id')->constrained('management_companies')->restrictOnDelete();
        $table->unique(['company_id', 'id']);
        $table->timestamps();
    }

    private function companyForeign(Blueprint $table, string $column, string $target, bool $nullable = false): void
    {
        $table->unsignedBigInteger($column)->nullable($nullable);
        $table->foreign(['company_id', $column])->references(['company_id', 'id'])->on($target)->restrictOnDelete();
    }

    public function down(): void
    {
        foreach (['audit_logs', 'documents', 'announcements', 'maintenance_requests', 'rent_payments', 'lease_contracts', 'tenants', 'units', 'buildings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'id']);
            $table->dropColumn(['company_id', 'phone', 'avatar', 'status']);
        });
        Schema::dropIfExists('management_companies');
    }
};
