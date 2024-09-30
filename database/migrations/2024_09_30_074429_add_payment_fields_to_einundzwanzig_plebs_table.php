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
        Schema::table('einundzwanzig_plebs', function (Blueprint $table) {
            $table->string('payment_event', 255 * 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('einundzwanzig_plebs', function (Blueprint $table) {
            //
        });
    }
};
