<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use Psr\Log\LoggerInterface;

/**
 * Transparent Bridge decorator that traces every native call (method + params
 * out, response in) at debug level. Bound in front of the real NativeBridge
 * only when config('admob.debug') is true. Never sits in front of the
 * FakeBridge - Admob::fake() replaces the Bridge binding entirely.
 */
final class LoggingBridge implements Bridge
{
    public function __construct(
        private Bridge $inner,
        private LoggerInterface $log,
    ) {}

    public function call(string $method, array $params = []): array
    {
        $this->log->debug('Admob bridge -> call', ['method' => $method, 'params' => $params]);

        $response = $this->inner->call($method, $params);

        $this->log->debug('Admob bridge <- result', ['method' => $method, 'response' => $response]);

        return $response;
    }
}
