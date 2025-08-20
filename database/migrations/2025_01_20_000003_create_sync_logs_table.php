<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_configuration_id')->constrained()->onDelete('cascade');
            $table->string('status'); // pending, running, completed, failed
            $table->integer('tasks_synced')->default(0);
            $table->integer('tasks_created')->default(0);
            $table->integer('tasks_updated')->default(0);
            $table->integer('tasks_failed')->default(0);
            $table->json('error_details')->nullable();
            $table->json('sync_summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            $table->index(['sync_configuration_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};