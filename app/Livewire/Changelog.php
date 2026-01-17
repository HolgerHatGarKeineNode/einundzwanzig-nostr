<?php

namespace App\Livewire;

use Livewire\Component;

final class Changelog extends Component
{
    public array $entries = [];

    public function mount(): void
    {
        $output = shell_exec('git log -n1000 --pretty=format:"%H|%s|%an|%ad" --date=format:"%Y-%m-%d %H:%M:%S"');
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
