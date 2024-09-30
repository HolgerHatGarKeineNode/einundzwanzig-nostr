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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('einundzwanzig_pleb_id');
            $table->foreign('einundzwanzig_pleb_id')->references('id')->on('einundzwanzig_plebs')->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->unsignedInteger('amount');
            $table->string('event_id', 255 * 2)->nullable();
            $table->timestamps();
        });

        Schema::table('einundzwanzig_plebs', function (Blueprint $table) {
            $table->dropColumn('payment_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
