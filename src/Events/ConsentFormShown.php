<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsentFormShown
{
    use Dispatchable;
    use SerializesModels;
}
