<?php

namespace App\Console\Commands\Nostr;

use App\Models\EinundzwanzigPleb;
use App\Traits\NostrFetcherTrait;
use Illuminate\Console\Command;

class SyncProfiles extends Command
{
    use NostrFetcherTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:profiles {--all : Fetch all plebs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = EinundzwanzigPleb::query();

        if (! $this->option('all')) {
            $query->whereDoesntHave('profile');
        }

        $plebs = $query->get();
        $count = $plebs->count();

        $this->info("\n🔄 Syncing profiles...");

        if ($count > 0) {
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            $this->fetchProfile($plebs->pluck('npub')->toArray());

            $bar->finish();
            $this->info("\n✅ Successfully synced $count profiles!");
        } else {
            $this->info('⚡ No profiles to sync!');
        }
    }
}
