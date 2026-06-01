<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a full-screen show() is suppressed by a configured
 * frequency cap. $reason is 'cooldown' or 'daily_cap'.
 */
class AdShowThrottled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $slot,
        public string $format,
        public string $reason,
    ) {}
}
