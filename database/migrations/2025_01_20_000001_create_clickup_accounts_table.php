<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clickup_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('account_type')->default('personal'); // personal or oauth
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('clickup_user_id')->nullable();
            $table->string('clickup_username')->nullable();
            $table->string('clickup_email')->nullable();
            $table->json('workspaces')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('clickup_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clickup_accounts');
    }
};