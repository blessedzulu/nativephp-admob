<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserEarnedReward
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $slot,
        public string $type,
        public int $amount,
    ) {}
}
