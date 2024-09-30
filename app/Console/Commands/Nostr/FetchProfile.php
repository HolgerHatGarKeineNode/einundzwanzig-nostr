<?php

namespace App\Console\Commands\Nostr;

use App\Traits\NostrFetcherTrait;
use Illuminate\Console\Command;

class FetchProfile extends Command
{
    use NostrFetcherTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nostr:pofile {--pubkey=}';

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
        $pubkey = $this->option('pubkey');
        if (empty($pubkey)) {
            $this->error('Please provide a pubkey');
            return;
        }

        $this->fetchProfile([$pubkey]);
    }
}
