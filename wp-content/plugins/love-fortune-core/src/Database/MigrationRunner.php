<?php
declare(strict_types=1);

namespace LoveFortune\Core\Database;

use InvalidArgumentException;
use RuntimeException;

/** Explicit activation-time runner. T01 ships no migrations and performs no DB writes. */
final class MigrationRunner
{
    private const COMPLETED = 'love_fortune_completed_migrations';
    private const LOCK = 'love_fortune_migration_lock';

    public function __construct(private readonly array $migrations)
    {
        $ids = [];
        foreach ($migrations as $migration) {
            if (!$migration instanceof MigrationInterface) {
                throw new InvalidArgumentException('Invalid migration registration.');
            }
            $id = $migration->id();
            if ($id === '' || in_array($id, $ids, true)) {
                throw new InvalidArgumentException('Migration IDs must be unique and nonempty.');
            }
            $ids[] = $id;
        }
    }

    public function run(bool $network_wide = false): void
    {
        if ($this->migrations === []) {
            return;
        }
        // Per-site provisioning requires an explicit design before T03 migrations ship.
        if ($network_wide || is_multisite()) {
            throw new RuntimeException('Multisite migrations are not supported.');
        }
        if (!add_option(self::LOCK, 'running', '', false)) {
            throw new RuntimeException('Migration execution is locked.');
        }
        try {
            $completed = get_option(self::COMPLETED, []);
            if (!is_array($completed) || count(array_filter($completed, 'is_string')) !== count($completed)) {
                throw new RuntimeException('Invalid migration journal.');
            }
            foreach ($this->migrations as $migration) {
                if (in_array($migration->id(), $completed, true)) {
                    continue;
                }
                $migration->up();
                $completed[] = $migration->id();
                if (!update_option(self::COMPLETED, $completed, false)) {
                    throw new RuntimeException('Cannot record completed migration.');
                }
            }
        } finally {
            delete_option(self::LOCK);
        }
    }
}
