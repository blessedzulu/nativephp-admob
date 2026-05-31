<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use BlessedZulu\NativePhpAdmob\Exceptions\UnknownSlotException;

class SlotResolver
{
    /**
     * @param  array<string, mixed>  $config  The full 'admob' config array
     */
    public function __construct(protected array $config) {}

    public function resolve(string $format, string $slot): string
    {
        if ($this->config['test_mode'] ?? false) {
            return TestAdUnits::for($format);
        }

        $unit = $this->config['slots'][$format][$slot] ?? null;

        if (! is_string($unit) || $unit === '') {
            throw new UnknownSlotException(
                "Slot [{$slot}] is not configured for format [{$format}]. ".
                "Add an entry under config('admob.slots.{$format}.{$slot}') or enable test_mode."
            );
        }

        return $unit;
    }
}
