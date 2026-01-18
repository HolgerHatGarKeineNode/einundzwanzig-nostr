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
            $table->text('archived_application_text')->nullable()->after('application_text');
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
