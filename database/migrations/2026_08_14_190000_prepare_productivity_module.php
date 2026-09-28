<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{
if(!Schema::hasTable('notes'))Schema::create('notes',function(Blueprint $t){$t->id();$t->string('title');$t->text('content')->nullable();$t->timestamps();});
if(!Schema::hasTable('calendar_reminders'))Schema::create('calendar_reminders',function(Blueprint $t){$t->id();$t->date('event_date');$t->string('title');$t->text('description')->nullable();$t->boolean('is_done')->default(false);$t->timestamps();$t->text('comments')->nullable();});
if(!Schema::hasTable('backup_runs'))Schema::create('backup_runs',function(Blueprint $t){$t->id();$t->string('backup_type',30);$t->string('period_key',20);$t->string('filename');$t->unsignedBigInteger('file_size')->nullable();$t->timestamp('created_at')->useCurrent();$t->unique(['backup_type','period_key']);});
} public function down():void{/* Historical data is intentionally preserved. */} };
