<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SecurityAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'method',
        'url',
        'route_name',
        'exception_class',
        'exception_message',
        'component_name',
        'target_property',
        'payload',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForComponent(Builder $query, string $component): Builder
    {
        return $query->where('component_name', $component);
    }
}
