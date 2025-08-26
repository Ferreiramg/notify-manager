<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('channel');
            $table->json('conditions');
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('max_sends_per_day')->default(0);
            $table->integer('max_sends_per_hour')->default(0);
            $table->json('allowed_days')->nullable();
            $table->json('allowed_hours')->nullable();
            $table->integer('priority')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['channel', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
