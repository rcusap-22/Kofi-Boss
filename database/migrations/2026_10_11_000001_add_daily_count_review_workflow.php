<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('daily_stock_counts',function(Blueprint $t){$t->dropUnique(['count_date']);$t->string('review_status')->default('submitted');$t->boolean('was_corrected')->default(false);$t->timestamp('canceled_at')->nullable();});
  Schema::create('daily_count_requests',function(Blueprint $t){$t->id();$t->foreignId('daily_stock_count_id')->constrained('daily_stock_counts');$t->foreignId('requested_by')->constrained('users');$t->foreignId('reviewed_by')->nullable()->constrained('users');$t->string('type');$t->string('status')->default('pending');$t->text('reason');$t->text('review_note')->nullable();$t->timestamp('reviewed_at')->nullable();$t->timestamps();});
  Schema::create('daily_count_request_items',function(Blueprint $t){$t->id();$t->foreignId('daily_count_request_id')->constrained('daily_count_requests');$t->foreignId('daily_stock_count_item_id')->constrained('daily_stock_count_items');$t->integer('previous_qty');$t->integer('requested_qty');$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('daily_count_request_items');Schema::dropIfExists('daily_count_requests');Schema::table('daily_stock_counts',function(Blueprint $t){$t->dropColumn(['review_status','was_corrected','canceled_at']);$t->unique('count_date');});}
};
