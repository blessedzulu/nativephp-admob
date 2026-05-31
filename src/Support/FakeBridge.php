<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use Closure;
use PHPUnit\Framework\Assert as PHPUnit;

class FakeBridge implements Bridge
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @var array<string, array{success: bool, data?: mixed, error?: ?string}> */
    public array $stubs = [];

    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        return $this->stubs[$method] ?? ['success' => true, 'data' => null, 'error' => null];
    }

    /**
     * Dispatch one of the plugin's Laravel events as if it came from native code.
     *
     * @param  array<int|string, mixed>  $constructorArgs
     */
    public function simulateEvent(string $eventClass, array $constructorArgs = []): void
    {
        event(new $eventClass(...$constructorArgs));
    }

    /**
     * Pre-program the response for a specific bridge method.
     *
     * @param  array{success: bool, data?: mixed, error?: ?string}  $response
     */
    public function stub(string $method, array $response): self
    {
        $this->stubs[$method] = $response;

        return $this;
    }

    public function assertCalled(string $method, ?Closure $matcher = null): void
    {
        $matched = array_filter(
            $this->calls,
            fn (array $call) => $call['method'] === $method
                && ($matcher === null || $matcher($call['params']))
        );

        PHPUnit::assertNotEmpty(
            $matched,
            "Expected bridge method [{$method}] to have been called, but it was not."
        );
    }

    public function assertNotCalled(string $method): void
    {
        $matched = array_filter($this->calls, fn (array $call) => $call['method'] === $method);

        PHPUnit::assertEmpty(
            $matched,
            "Expected bridge method [{$method}] to not have been called, but it was."
        );
    }

    public function assertCalledTimes(string $method, int $times): void
    {
        $count = count(array_filter($this->calls, fn (array $call) => $call['method'] === $method));

        PHPUnit::assertSame(
            $times,
            $count,
            "Expected bridge method [{$method}] to have been called {$times} time(s), but it was called {$count} time(s)."
        );
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->stubs = [];
    }
}
