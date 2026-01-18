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
        Schema::create('rendered_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64)->unique()->index();
            $table->foreign('event_id')->references('event_id')->on('events')->cascadeOnDelete();
            $table->text('html');
            $table->string('profile_image');
            $table->string('profile_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendered_events');
    }
};
