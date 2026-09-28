<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if(!Schema::hasTable('microsoft_emails')) Schema::create('microsoft_emails',function(Blueprint $t){$t->id();$t->string('email')->unique();$t->string('password')->nullable();$t->string('status',50)->default('ACTIVA');$t->date('activation_date')->nullable();$t->date('renewal_date')->nullable();$t->string('admin_url')->nullable();$t->string('admin_account')->nullable();$t->timestamps();$t->text('comments')->nullable();});
        if(!Schema::hasTable('office_emails')) Schema::create('office_emails',function(Blueprint $t){$t->id();$t->string('email')->unique();$t->string('password')->nullable();$t->string('status',50)->default('ACTIVA');$t->date('activation_date')->nullable();$t->date('renewal_date')->nullable();$t->timestamps();$t->text('comments')->nullable();});
        if(!Schema::hasTable('email_backups')) Schema::create('email_backups',function(Blueprint $t){$t->id();$t->string('original_name');$t->string('original_email');$t->string('backup_name');$t->string('backup_email');$t->date('start_date')->nullable();$t->date('end_date')->nullable();$t->boolean('is_done')->default(false);$t->boolean('is_archived')->default(false);$t->timestamps();$t->text('comments')->nullable();});
    }
    public function down(): void { /* Imported email data is intentionally preserved. */ }
};
