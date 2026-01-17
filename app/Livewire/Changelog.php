<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Process;
use Livewire\Component;

final class Changelog extends Component
{
    public array $entries = [];

    public function mount(): void
    {
        $process = Process::fromShellCommandline('git log -n1000 --pretty=format:"%H|%s|%an|%ad" --date=format:"%Y-%m-%d %H:%M:%S"');
        $process->run();
        $output = $process->getOutput();
        $lines = explode("\n", trim($output));
        $entries = [];

        foreach ($lines as $line) {
            [$hash, $message, $author, $date] = explode('|', $line);
            $entries[] = [
                'hash' => $hash,
                'message' => $message,
                'author' => $author,
                'date' => $date,
            ];
        }
        $this->entries = $entries;
    }
}
