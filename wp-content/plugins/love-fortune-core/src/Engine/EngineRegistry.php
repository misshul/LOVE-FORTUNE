<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine;

use InvalidArgumentException;
use OutOfBoundsException;

/** Request-local dependencies; calculation interfaces are defined by their future tasks. */
final class EngineRegistry
{
    public function __construct(private readonly array $engines)
    {
        foreach ($engines as $id => $engine) {
            if (!is_string($id) || $id === '' || !is_object($engine)) {
                throw new InvalidArgumentException('Invalid engine registration.');
            }
        }
    }

    public function get(string $id): object
    {
        return $this->engines[$id] ?? throw new OutOfBoundsException('Engine is not registered.');
    }

    public function has(string $id): bool
    {
        return isset($this->engines[$id]);
    }
}
