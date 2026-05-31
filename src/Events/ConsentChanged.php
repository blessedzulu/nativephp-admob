<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsentChanged
{
    use Dispatchable;
    use SerializesModels;

    public const STATUS_REQUIRED = 'required';

    public const STATUS_OBTAINED = 'obtained';

    public const STATUS_NOT_REQUIRED = 'not_required';

    public const STATUS_UNKNOWN = 'unknown';

    public function __construct(public string $status) {}
}
