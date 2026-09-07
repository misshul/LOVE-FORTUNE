<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

use InvalidArgumentException;

/** Runs explicit route/admin registrars at the appropriate WordPress lifecycle hook. */
final class HookLoader
{
    private bool $loaded = false;

    public function __construct(private readonly array $registrations)
    {
        foreach ($registrations as $registration) {
            if (!$registration instanceof RegistrationInterface) {
                throw new InvalidArgumentException('Invalid module registration.');
            }
        }
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }
        foreach ($this->registrations as $registration) {
            $registration->register();
        }
        $this->loaded = true;
    }
}
