<?php

use App\Models\SecurityAttempt;
use App\Services\SecurityMonitor;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

it('records a security attempt when a locked-property exception is reported through the handler', function () {
    $exception = new CannotUpdateLockedPropertyException('isLoggedIn');

    app(ExceptionHandler::class)->report($exception);

    expect(SecurityAttempt::count())->toBe(1)
        ->and(SecurityAttempt::first()->target_property)->toBe('isLoggedIn');
});

it('does not forward locked-property exceptions to the default log stack', function () {
    Log::spy();

    app(ExceptionHandler::class)->report(new CannotUpdateLockedPropertyException('isLoggedIn'));

    expect(SecurityAttempt::count())->toBe(1);

    Log::shouldNotHaveReceived('log');
    Log::shouldNotHaveReceived('error');
    Log::shouldNotHaveReceived('critical');
    Log::shouldNotHaveReceived('warning');
});

it('still forwards non-security exceptions to the default log stack', function () {
    Log::spy();

    app(ExceptionHandler::class)->report(new RuntimeException('boom'));

    expect(SecurityAttempt::count())->toBe(0);

    Log::shouldHaveReceived('error');
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
