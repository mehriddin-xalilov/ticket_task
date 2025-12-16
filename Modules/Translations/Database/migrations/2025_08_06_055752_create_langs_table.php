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
        Schema::create('langs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable(false);
            $table->string('code')->nullable(false);
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('system_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('category');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('system_message_translation', function (Blueprint $table) {
            $table->integer('id');
            $table->string('language');
            $table->text('translation');
            $table->index(['id', 'language']);
            $table->foreign('id')->references('id')->on('system_message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('langs');
        Schema::dropIfExists('_system_message');
        Schema::dropIfExists('_system_message_translation');
    }
};
