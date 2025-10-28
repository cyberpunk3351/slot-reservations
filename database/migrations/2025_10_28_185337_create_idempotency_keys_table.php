<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('method');
            $table->string('endpoint');
            $table->unsignedSmallInteger('response_code');
            $table->foreignId('hold_id')->nullable()->constrained('holds')->nullOnDelete();
            $table->json('response_body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('idempotency_keys');
    }
};
