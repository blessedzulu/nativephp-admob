<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Exceptions\BridgeUnavailableException;

class NativeBridge implements Bridge
{
    public function call(string $method, array $params = []): array
    {
        if (! function_exists('nativephp_call')) {
            throw new BridgeUnavailableException(
                'NativePHP bridge unavailable. Run inside a NativePHP build, or call Admob::fake() in your tests.'
            );
        }

        $raw = nativephp_call($method, json_encode($params, JSON_THROW_ON_ERROR));
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return ['success' => false, 'data' => null, 'error' => 'Invalid bridge response'];
        }

        return $decoded + ['success' => false, 'data' => null, 'error' => null];
    }
}
