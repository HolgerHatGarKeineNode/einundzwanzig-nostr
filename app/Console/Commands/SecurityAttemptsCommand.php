<?php

namespace App\Console\Commands;

use App\Models\SecurityAttempt;
use Illuminate\Console\Command;

class SecurityAttemptsCommand extends Command
{
    protected $signature = 'security:attempts
        {--hours=24 : Show attempts from the last N hours}
        {--ip= : Filter by IP address}
        {--severity= : Filter by severity (low, medium, high)}
        {--component= : Filter by component name}
        {--stats : Show statistics only}
        {--top-ips=10 : Show top N attacking IPs}';

    protected $description = 'View and analyze security attack attempts';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        if ($this->option('stats')) {
            return $this->showStats($hours);
        }

        if ($this->option('top-ips')) {
            return $this->showTopIps($hours, (int) $this->option('top-ips'));
        }

        return $this->showAttempts($hours);
    }

    private function showAttempts(int $hours): int
    {
        $query = SecurityAttempt::query()->recent($hours);

        if ($ip = $this->option('ip')) {
            $query->fromIp($ip);
        }

        if ($severity = $this->option('severity')) {
            $query->severity($severity);
        }

        if ($component = $this->option('component')) {
            $query->forComponent($component);
        }

        $attempts = $query->latest()->limit(100)->get();

        if ($attempts->isEmpty()) {
            $this->info("No security attempts found in the last {$hours} hours.");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Time', 'IP', 'Severity', 'Component', 'Property', 'Exception'],
            $attempts->map(fn (SecurityAttempt $a) => [
                $a->id,
                $a->created_at->format('Y-m-d H:i:s'),
                $a->ip_address,
                $this->formatSeverity($a->severity),
                $a->component_name ?? '-',
                $a->target_property ?? '-',
                class_basename($a->exception_class),
            ])
        );

        $this->newLine();
        $this->info("Showing {$attempts->count()} attempts (max 100). Use filters to narrow down.");

        return self::SUCCESS;
    }

    private function showStats(int $hours): int
    {
        $total = SecurityAttempt::query()->recent($hours)->count();
        $bySeverity = SecurityAttempt::query()
            ->recent($hours)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $uniqueIps = SecurityAttempt::query()
            ->recent($hours)
            ->distinct('ip_address')
            ->count('ip_address');

        $topComponents = SecurityAttempt::query()
            ->recent($hours)
            ->whereNotNull('component_name')
            ->selectRaw('component_name, COUNT(*) as count')
            ->groupBy('component_name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'component_name')
            ->toArray();

        $this->info("Security Attempts - Last {$hours} hours");
        $this->newLine();

        $this->table(['Metric', 'Value'], [
            ['Total Attempts', $total],
            ['Unique IPs', $uniqueIps],
            ['High Severity', $bySeverity['high'] ?? 0],
            ['Medium Severity', $bySeverity['medium'] ?? 0],
            ['Low Severity', $bySeverity['low'] ?? 0],
        ]);

        if (! empty($topComponents)) {
            $this->newLine();
            $this->info('Top Targeted Components:');
            $this->table(
                ['Component', 'Attempts'],
                collect($topComponents)->map(fn ($count, $name) => [$name, $count])->toArray()
            );
        }

        return self::SUCCESS;
    }

    private function showTopIps(int $hours, int $limit): int
    {
        $ips = SecurityAttempt::query()
            ->recent($hours)
            ->selectRaw('ip_address, COUNT(*) as count, MAX(severity) as max_severity')
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        if ($ips->isEmpty()) {
            $this->info("No security attempts found in the last {$hours} hours.");

            return self::SUCCESS;
        }

        $this->info("Top {$limit} Attacking IPs - Last {$hours} hours");
        $this->newLine();

        $this->table(
            ['IP Address', 'Attempts', 'Max Severity'],
            $ips->map(fn ($row) => [
                $row->ip_address,
                $row->count,
                $this->formatSeverity($row->max_severity),
            ])
        );

        return self::SUCCESS;
    }

    private function formatSeverity(string $severity): string
    {
        return match ($severity) {
            'high' => '<fg=red>HIGH</>',
            'medium' => '<fg=yellow>MEDIUM</>',
            'low' => '<fg=green>LOW</>',
            default => $severity,
        };
    }
}
