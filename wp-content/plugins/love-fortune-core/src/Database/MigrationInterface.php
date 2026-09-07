<?php
declare(strict_types=1);

namespace LoveFortune\Core\Database;

interface MigrationInterface
{
    public function id(): string;

    /** Must be idempotent: MySQL DDL may commit before the completion marker is saved. */
    public function up(): void;
}
