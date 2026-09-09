<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->timestamp('upcoming_notified_at')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->unique(['lease_contract_id', 'due_date'], 'payments_lease_due_unique');
        });
        Schema::create('maintenance_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('management_companies')->restrictOnDelete();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->foreign(['company_id', 'maintenance_request_id'], 'activities_request_company')
                ->references(['company_id', 'id'])->on('maintenance_requests')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'maintenance_request_id', 'id'], 'activities_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_activities');
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->dropUnique('payments_lease_due_unique');
            $table->dropColumn(['upcoming_notified_at', 'overdue_notified_at']);
        });
    }
};
