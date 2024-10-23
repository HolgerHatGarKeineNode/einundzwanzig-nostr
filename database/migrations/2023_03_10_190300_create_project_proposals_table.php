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
        Schema::disableForeignKeyConstraints();

        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('einundzwanzig_pleb_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->unsignedBigInteger('support_in_sats');
            $table->text('description');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
    }
};
