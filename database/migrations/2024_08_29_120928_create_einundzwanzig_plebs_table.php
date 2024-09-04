<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('einundzwanzig_plebs', function (Blueprint $table) {
            $table->id();
            $table->string('npub', 63);
            $table->string('pubkey', 64)->unique()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('einundzwanzig_plebs');
    }
};
