<?php

use App\Models\SecurityAttempt;
use App\Services\SecurityMonitor;
use Illuminate\Http\Request;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

beforeEach(function () {
    $this->monitor = app(SecurityMonitor::class);
});

it('records locked property exceptions', function () {
    $exception = new CannotUpdateLockedPropertyException('isLoggedIn');

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            [
                'snapshot' => json_encode([
                    'memo' => ['name' => 'auth-button'],
                ]),
                'updates' => ['isLoggedIn' => true],
            ],
        ],
    ]);

    $request->setRouteResolver(fn () => null);

    app()->instance('request', $request);

    $this->monitor->recordFromException($exception, $request);

    expect(SecurityAttempt::count())->toBe(1);

    $attempt = SecurityAttempt::first();
    expect($attempt->exception_class)->toBe(CannotUpdateLockedPropertyException::class)
        ->and($attempt->target_property)->toBe('isLoggedIn')
        ->and($attempt->component_name)->toBe('auth-button')
        ->and($attempt->severity)->toBe('medium');
});

it('detects high severity injection attempts', function () {
    $exception = new CannotUpdateLockedPropertyException('isLoggedIn');

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            [
                'snapshot' => json_encode([
                    'memo' => ['name' => 'auth-button'],
                ]),
                'updates' => [
                    'isLoggedIn' => [
                        '__toString' => 'phpinfo',
                        'SerializableClosure' => [],
                    ],
                ],
            ],
        ],
    ]);

    $request->setRouteResolver(fn () => null);

    $this->monitor->recordFromException($exception, $request);

    $attempt = SecurityAttempt::first();
    expect($attempt->severity)->toBe('high');
});

it('does not record non-security exceptions', function () {
    $exception = new RuntimeException('Something went wrong');

    $this->monitor->recordFromException($exception);

    expect(SecurityAttempt::count())->toBe(0);
});

it('never throws exceptions itself', function () {
    $exception = new CannotUpdateLockedPropertyException('test');

    // Create a request that might cause issues
    $request = Request::create('/test', 'POST');
    $request->setRouteResolver(fn () => null);

    // This should not throw even if there are issues
    $this->monitor->recordFromException($exception, $request);

    // If we get here without exception, the test passes
    expect(true)->toBeTrue();
});

it('tracks attempts from same IP', function () {
    $exception = new CannotUpdateLockedPropertyException('test');

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            ['snapshot' => '{}', 'updates' => []],
        ],
    ], server: ['REMOTE_ADDR' => '192.168.1.100']);

    $request->setRouteResolver(fn () => null);

    // Record multiple attempts
    $this->monitor->recordFromException($exception, $request);
    $this->monitor->recordFromException($exception, $request);
    $this->monitor->recordFromException($exception, $request);

    expect($this->monitor->getAttemptsFromIp('192.168.1.100'))->toBe(3);
});

it('identifies suspicious IPs', function () {
    $exception = new CannotUpdateLockedPropertyException('test');

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            ['snapshot' => '{}', 'updates' => []],
        ],
    ], server: ['REMOTE_ADDR' => '10.0.0.1']);

    $request->setRouteResolver(fn () => null);

    // Record 10 attempts (threshold)
    for ($i = 0; $i < 10; $i++) {
        $this->monitor->recordFromException($exception, $request);
    }

    expect($this->monitor->isIpSuspicious('10.0.0.1', threshold: 10))->toBeTrue()
        ->and($this->monitor->isIpSuspicious('10.0.0.2', threshold: 10))->toBeFalse();
});

it('truncates long values', function () {
    $exception = new CannotUpdateLockedPropertyException('test');

    $longUserAgent = str_repeat('a', 1000);

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            ['snapshot' => '{}', 'updates' => []],
        ],
    ], server: ['HTTP_USER_AGENT' => $longUserAgent]);

    $request->setRouteResolver(fn () => null);

    $this->monitor->recordFromException($exception, $request);

    $attempt = SecurityAttempt::first();
    expect(strlen($attempt->user_agent))->toBeLessThanOrEqual(500);
});

it('handles X-Forwarded-For header', function () {
    $exception = new CannotUpdateLockedPropertyException('test');

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            ['snapshot' => '{}', 'updates' => []],
        ],
    ], server: [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.50, 70.41.3.18',
    ]);

    $request->setRouteResolver(fn () => null);

    $this->monitor->recordFromException($exception, $request);

    $attempt = SecurityAttempt::first();
    expect($attempt->ip_address)->toBe('203.0.113.50');
});
