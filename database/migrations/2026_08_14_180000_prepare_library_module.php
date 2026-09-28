<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{
if(!Schema::hasTable('tutorials'))Schema::create('tutorials',function(Blueprint $t){$t->id();$t->string('title');$t->string('category',100)->nullable();$t->text('content_url')->nullable();$t->text('description')->nullable();$t->timestamps();$t->text('comments')->nullable();});
if(!Schema::hasTable('gallery'))Schema::create('gallery',function(Blueprint $t){$t->id();$t->string('title');$t->string('filename');$t->text('notes')->nullable();$t->timestamp('upload_date')->useCurrent();});
if(!Schema::hasTable('ftp_catalog'))Schema::create('ftp_catalog',function(Blueprint $t){$t->id();$t->string('original_name');$t->string('alias_name')->nullable();$t->text('file_path')->nullable();$t->unsignedBigInteger('file_size')->nullable();$t->timestamp('upload_date')->useCurrent();$t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();$t->text('comments')->nullable();$t->timestamps();});
} public function down():void{/* Imported library data is intentionally preserved. */} };
