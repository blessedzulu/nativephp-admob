<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Date;

/**
 * Per-format / per-slot show throttling backed by the cache so caps survive
 * app relaunches. Two independent constraints, both opt-in (0 or missing means
 * disabled):
 *   - min_interval_seconds: a cooldown between consecutive shows of a slot
 *   - max_per_day: a rolling daily cap (resets at local midnight)
 *
 * Per-slot rules (config('admob.frequency.slots.{format}.{slot}')) override the
 * per-format defaults (config('admob.frequency.{format}')). test_mode bypasses
 * all caps so developers can spam-test.
 */
final class FrequencyCap
{
    /**
     * @param  array<string, mixed>  $config  the full 'admob' config array
     */
    public function __construct(
        private Repository $cache,
        private array $config,
    ) {}

    public function allows(string $format, string $slot): bool
    {
        return $this->reason($format, $slot) === null;
    }

    /**
     * Why a show would be blocked: 'cooldown', 'daily_cap', or null if allowed.
     */
    public function reason(string $format, string $slot): ?string
    {
        if ($this->config['test_mode'] ?? false) {
            return null;
        }

        $rules = $this->rulesFor($format, $slot);
        $minInterval = (int) ($rules['min_interval_seconds'] ?? 0);
        $maxPerDay = (int) ($rules['max_per_day'] ?? 0);

        if ($minInterval > 0) {
            $last = $this->cache->get($this->lastKey($format, $slot));
            if (is_numeric($last) && (Date::now()->getTimestamp() - (int) $last) < $minInterval) {
                return 'cooldown';
            }
        }

        if ($maxPerDay > 0 && (int) $this->cache->get($this->countKey($format, $slot), 0) >= $maxPerDay) {
            return 'daily_cap';
        }

        return null;
    }

    public function record(string $format, string $slot): void
    {
        if ($this->config['test_mode'] ?? false) {
            return;
        }

        $now = Date::now();
        $ttl = $this->secondsUntilEndOfDay($now);

        $this->cache->put($this->lastKey($format, $slot), $now->getTimestamp(), $ttl);

        $countKey = $this->countKey($format, $slot);
        $this->cache->put($countKey, ((int) $this->cache->get($countKey, 0)) + 1, $ttl);
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesFor(string $format, string $slot): array
    {
        $freq = $this->config['frequency'] ?? [];
        $perFormat = is_array($freq[$format] ?? null) ? $freq[$format] : [];
        $perSlot = is_array($freq['slots'][$format][$slot] ?? null) ? $freq['slots'][$format][$slot] : [];

        return array_merge($perFormat, $perSlot);
    }

    private function lastKey(string $format, string $slot): string
    {
        return "admob:freq:last:{$format}:{$slot}";
    }

    private function countKey(string $format, string $slot): string
    {
        return "admob:freq:count:{$format}:{$slot}:".Date::now()->format('Ymd');
    }

    private function secondsUntilEndOfDay(DateTimeInterface $now): int
    {
        return max(1, (int) round(abs(Date::instance($now)->diffInSeconds(Date::instance($now)->copy()->endOfDay()))));
    }
}
