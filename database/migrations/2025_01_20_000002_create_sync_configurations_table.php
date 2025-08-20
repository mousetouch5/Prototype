<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Source configuration
            $table->foreignId('source_account_id')->constrained('clickup_accounts')->onDelete('cascade');
            $table->string('source_workspace_id');
            $table->string('source_space_id')->nullable();
            $table->string('source_folder_id')->nullable();
            $table->string('source_list_id')->nullable();
            
            // Target configuration
            $table->foreignId('target_account_id')->constrained('clickup_accounts')->onDelete('cascade');
            $table->string('target_workspace_id');
            $table->string('target_space_id')->nullable();
            $table->string('target_folder_id')->nullable();
            $table->string('target_list_id')->nullable();
            
            // Sync settings
            $table->json('sync_options')->nullable(); // field mappings, filters, transformations
            $table->string('sync_direction')->default('one_way'); // one_way, two_way
            $table->string('conflict_resolution')->default('source_wins'); // source_wins, target_wins, manual
            $table->boolean('sync_attachments')->default(false);
            $table->boolean('sync_comments')->default(false);
            $table->boolean('sync_custom_fields')->default(true);
            
            // Schedule settings
            $table->string('schedule_type')->default('manual'); // manual, interval, cron
            $table->integer('schedule_interval')->nullable(); // in minutes
            $table->string('schedule_cron')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('next_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_configurations');
    }
};