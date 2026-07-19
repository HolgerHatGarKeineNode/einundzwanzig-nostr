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
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->boolean('contact_via_nostr_dm')->default(true);
            $table->string('contact_alternative')->nullable();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->index(['project_proposal_id', 'value'], 'votes_proposal_value_index');
            $table->index(['einundzwanzig_pleb_id', 'value'], 'votes_pleb_value_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex('votes_proposal_value_index');
            $table->dropIndex('votes_pleb_value_index');
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['contact_via_nostr_dm', 'contact_alternative']);
        });
    }
};
