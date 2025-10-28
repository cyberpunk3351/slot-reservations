<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->enum('status', ['held', 'confirmed', 'cancelled'])->index();
            $table->string('idempotency_key')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['slot_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('holds');
    }
};
