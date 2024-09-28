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
    protected $signature = 'sync:profiles';

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
        $plebs = EinundzwanzigPleb::query()
            ->whereDoesntHave('profile')
            ->get();
        $this->fetchProfile($plebs->pluck('npub')->toArray());
    }
}
