<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cellphones')) {
            Schema::create('cellphones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('employee_name_legacy', 150)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('area', 100)->nullable();
                $table->string('email_account', 100)->nullable();
                $table->string('recovery_account', 100)->nullable();
                $table->string('phone_number', 20)->nullable();
                $table->string('password', 100)->nullable();
                $table->string('updated_password', 100)->nullable();
                $table->string('birth_date', 50)->nullable();
                $table->string('app_lock_password', 100)->nullable();
                $table->string('app_lock_answer')->nullable();
                $table->string('status', 50)->default('En Uso');
                $table->text('update_note')->nullable();
                $table->text('comments')->nullable();
                $table->boolean('has_app_lock')->default(false);
                $table->string('app_lock_pattern', 100)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asset_files')) {
            Schema::create('asset_files', function (Blueprint $table) {
                $table->id();
                $table->string('asset_type', 50);
                $table->unsignedBigInteger('asset_id');
                $table->string('original_name');
                $table->string('file_path');
                $table->string('file_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('label', 120)->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('uploaded_at')->useCurrent();
                $table->index(['asset_type', 'asset_id']);
            });
        }
    }

    public function down(): void
    {
        // Imported inventory data is intentionally preserved.
    }
};
