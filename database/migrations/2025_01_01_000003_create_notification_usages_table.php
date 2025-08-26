<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_usages', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id');
            $table->string('channel');
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamp('used_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['notification_id']);
            $table->index(['channel', 'used_at']);
            $table->index('used_at');
            $table->index(['cost', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_usages');
    }
};
