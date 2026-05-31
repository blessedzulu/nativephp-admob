<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Contracts;

interface Bridge
{
    /**
     * Invoke a native function and return its decoded result.
     *
     * The returned array always carries:
     *   - 'success' (bool) - whether the native call completed
     *   - 'data'    (mixed) - opaque payload from the native side
     *   - 'error'   (?string) - error message when success is false
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, data?: mixed, error?: ?string}
     */
    public function call(string $method, array $params = []): array;
}
