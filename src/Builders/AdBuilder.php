<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use BlessedZulu\NativePhpAdmob\Admob;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\AdShowThrottled;
use Illuminate\Support\Facades\Log;

/**
 * Shared base for the five ad-format builders. Centralises the constructor,
 * the params() payload, and a dispatch() helper that surfaces bridge failures
 * (a `success !== true` response) as a warning instead of swallowing them.
 *
 * Runtime ad failures are logged, never thrown - a failed ad must not crash
 * the host app. Programmer errors (e.g. an unknown slot) throw upstream in
 * SlotResolver via UnknownSlotException.
 */
abstract class AdBuilder
{
    public function __construct(
        protected Bridge $bridge,
        protected Admob $manager,
        protected string $slot,
        protected string $adUnitId,
    ) {}

    abstract protected function format(): string;

    /**
     * Call a bridge method with the standard params, logging any failure.
     *
     * @param  array<string, mixed>  $extra
     * @return array{success: bool, data?: mixed, error?: ?string}
     */
    protected function dispatch(string $method, array $extra = []): array
    {
        if (! $this->manager->enabled()) {
            Log::debug('Admob: disabled, skipping bridge call.', [
                'method' => $method,
                'slot' => $this->slot,
                'format' => $this->format(),
            ]);

            return ['success' => false, 'data' => null, 'error' => 'admob_disabled'];
        }

        $response = $this->bridge->call($method, $this->params($extra));

        if (($response['success'] ?? false) !== true) {
            Log::warning('Admob: bridge call failed.', [
                'method' => $method,
                'slot' => $this->slot,
                'format' => $this->format(),
                'error' => $response['error'] ?? null,
            ]);
        }

        return $response;
    }

    /**
     * Frequency-cap gate for full-screen formats. Returns false (and logs +
     * dispatches AdShowThrottled) when this slot is currently throttled.
     * Banner is a persistent overlay and does not call this.
     */
    protected function passesFrequencyCap(): bool
    {
        $cap = $this->manager->frequencyCap();

        if (! $cap->allows($this->format(), $this->slot)) {
            $reason = $cap->reason($this->format(), $this->slot);
            Log::info('Admob: show() throttled by frequency cap.', [
                'slot' => $this->slot,
                'format' => $this->format(),
                'reason' => $reason,
            ]);
            event(new AdShowThrottled($this->slot, $this->format(), (string) $reason));

            return false;
        }

        return true;
    }

    /**
     * Record a successful show against the frequency cap.
     */
    protected function recordShow(): void
    {
        $this->manager->frequencyCap()->record($this->format(), $this->slot);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function params(array $extra = []): array
    {
        return array_merge([
            'slot' => $this->slot,
            'format' => $this->format(),
            'unit_id' => $this->adUnitId,
        ], $extra);
    }
}
