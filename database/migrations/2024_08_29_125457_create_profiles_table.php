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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('pubkey', 64)->unique()->index();
            $table->string('name', 255 * 2)->nullable();
            $table->string('display_name', 255 * 2)->nullable();
            $table->text('picture')->nullable();
            $table->text('banner')->nullable();
            $table->string('website', 255 * 2)->nullable();
            $table->text('about')->nullable();
            $table->string('nip05', 255 * 2)->nullable();
            $table->string('lud16', 255 * 2)->nullable();
            $table->string('lud06', 255 * 2)->nullable();
            $table->boolean('deleted')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
