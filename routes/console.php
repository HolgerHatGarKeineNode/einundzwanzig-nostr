<?php

use App\Console\Commands\Nostr\SyncProfiles;
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command(SyncProfiles::class)->daily()->at('00:30');
