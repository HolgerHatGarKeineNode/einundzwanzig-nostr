<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Der private NIP-29-Chatraum eines Antrags. Die Raum-ID ist zwar aus der
     * Antrags-ID ableitbar, wird aber trotzdem gespeichert: Der Gruppen-Relay
     * verlangt NIP-42-AUTH schon zum Lesen, eine Existenzprüfung "gibt es den
     * Raum bereits?" kann also nicht keylos gegen den Relay laufen. Diese
     * Spalte ist die Wahrheitsquelle dafür — nicht der Relay.
     */
    public function up(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->string('nostr_group_h', 32)->nullable()->index();
            $table->timestamp('nostr_group_created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['nostr_group_h', 'nostr_group_created_at']);
        });
    }
};
