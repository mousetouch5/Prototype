<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sync_configurations', function (Blueprint $table) {
            $table->string('source_platform')->default('clickup')->after('source_account_id');
            $table->string('target_platform')->default('clickup')->after('target_account_id');
            $table->json('field_mapping')->nullable()->after('sync_options');
            $table->json('status_mapping')->nullable()->after('field_mapping');
            $table->json('user_mapping')->nullable()->after('status_mapping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'source_platform',
                'target_platform', 
                'field_mapping',
                'status_mapping',
                'user_mapping'
            ]);
        });
    }
};
