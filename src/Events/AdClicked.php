<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdClicked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $slot,
        public string $format,
    ) {}
}
