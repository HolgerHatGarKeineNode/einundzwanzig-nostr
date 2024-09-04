<?php

namespace App\Console\Commands\Nostr;

use App\Traits\NostrEventRendererTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;

class RenderAllEvents extends Command
{
    use NostrEventRendererTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'render';

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
        $events = \App\Models\Event::query()
            ->get();

        foreach ($events as $event) {
            $this->renderContentToHtml($event);
        }

        Broadcast::on('events')
            ->as('newEvents')
            ->with([
                'test' => 'test',
            ])
            ->sendNow();
    }
}
