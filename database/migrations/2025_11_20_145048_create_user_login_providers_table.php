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
        Schema::create('user_login_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->onDelete('cascade');
            $table->string('provider')->index(); // e.g., 'google', 'facebook', 'apple'
            $table->string('provider_id')->index(); // Unique ID from the provider
            $table->string('email')->nullable()->index(); // Email from the provider
            $table->string('full_name')->nullable(); // Name from the provider
            $table->text('photo')->nullable(); // Photo URL from the provider
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_providers');
    }
};
