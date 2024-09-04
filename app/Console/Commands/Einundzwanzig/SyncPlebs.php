<?php

namespace App\Console\Commands\Einundzwanzig;

use App\Models\EinundzwanzigPleb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use swentel\nostr\Key\Key;

class SyncPlebs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:plebs';

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
        $response = Http::get('https://portal.einundzwanzig.space/api/nostrplebs');

        $plebs = $response->json();

        foreach ($plebs as $pleb) {
            $npub = str($pleb)->trim();
            EinundzwanzigPleb::updateOrCreate(
                ['npub' => $npub],
                ['pubkey' => (new Key())->convertToHex($npub)]
            );
        }
    }
}
