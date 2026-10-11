<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_count_request_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_count_request_id')->constrained('daily_count_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['daily_count_request_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('daily_count_request_reads'); }
};
