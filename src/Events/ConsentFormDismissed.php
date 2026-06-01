<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsentFormDismissed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public string $status) {}
}
