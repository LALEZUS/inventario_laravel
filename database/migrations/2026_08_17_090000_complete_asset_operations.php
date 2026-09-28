<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignments')) {
            Schema::create('assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
                $table->string('asset_type', 50);
                $table->unsignedBigInteger('asset_id');
                $table->string('assigned_to', 150)->nullable();
                $table->string('department', 100)->nullable();
                $table->date('date_assigned');
                $table->date('date_returned')->nullable();
                $table->string('condition_on_assign', 50)->nullable();
                $table->string('condition_on_return', 50)->nullable();
                $table->text('notes')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->index(['asset_type', 'asset_id']);
            });
        }

        if (! Schema::hasTable('maintenance_logs')) {
            Schema::create('maintenance_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asset_id');
                $table->string('asset_type', 50);
                $table->date('date');
                $table->text('description');
                $table->string('technician', 100)->nullable();
                $table->decimal('cost', 10, 2)->default(0);
                $table->timestamps();
                $table->index(['asset_type', 'asset_id']);
            });
        }

        Schema::table('maintenance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_logs', 'category')) $table->string('category', 50)->nullable()->after('description');
            if (! Schema::hasColumn('maintenance_logs', 'diagnosis')) $table->text('diagnosis')->nullable()->after('category');
            if (! Schema::hasColumn('maintenance_logs', 'provider')) $table->string('provider', 120)->nullable()->after('technician');
            if (! Schema::hasColumn('maintenance_logs', 'next_date')) $table->date('next_date')->nullable()->after('cost');
            if (! Schema::hasColumn('maintenance_logs', 'status')) $table->string('status', 30)->default('Completado')->after('next_date');
            if (! Schema::hasColumn('maintenance_logs', 'updated_at')) $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        Schema::table('asset_files', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_files', 'assignment_id')) {
                $table->unsignedInteger('assignment_id')->nullable()->after('asset_id')->index();
            }
        });
    }

    public function down(): void
    {
        // Operational history is intentionally preserved during migration.
    }
};
