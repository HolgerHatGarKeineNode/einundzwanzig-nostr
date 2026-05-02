<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetup_user', function (Blueprint $table) {
            $table->foreignId('meetup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['meetup_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetup_user');
    }
};
