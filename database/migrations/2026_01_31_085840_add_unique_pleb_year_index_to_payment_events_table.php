<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payment_events')
            ->select('einundzwanzig_pleb_id', 'year', DB::raw('count(*) as total'))
            ->groupBy('einundzwanzig_pleb_id', 'year')
            ->havingRaw('count(*) > 1')
            ->get()
            ->each(function ($groupedPaymentEvent): void {
                $idsToKeep = DB::table('payment_events')
                    ->where('einundzwanzig_pleb_id', $groupedPaymentEvent->einundzwanzig_pleb_id)
                    ->where('year', $groupedPaymentEvent->year)
                    ->orderByDesc('paid')
                    ->orderByDesc('updated_at')
                    ->pluck('id')
                    ->toArray();

                $keep = array_shift($idsToKeep);

                if (! empty($idsToKeep)) {
                    DB::table('payment_events')
                        ->whereIn('id', $idsToKeep)
                        ->delete();
                }
            });

        Schema::table('payment_events', function (Blueprint $table): void {
            $table->unique(['einundzwanzig_pleb_id', 'year'], 'payment_events_pleb_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table): void {
            $table->dropUnique('payment_events_pleb_year_unique');
        });
    }
};
