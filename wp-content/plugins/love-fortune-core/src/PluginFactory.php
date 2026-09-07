<?php
declare(strict_types=1);

namespace LoveFortune\Core;

use LoveFortune\Core\Database\MigrationRunner;
use LoveFortune\Core\Engine\EngineRegistry;
use LoveFortune\Core\Support\ConfigLoader;
use LoveFortune\Core\Support\HookLoader;
use LoveFortune\Core\Support\VersionRegistry;

/** Composition root: concrete dependencies are created here and constructor-injected. */
final class PluginFactory
{
    public static function create(string $directory): Plugin
    {
        $config = (new ConfigLoader())->load($directory . '/config/bootstrap.php');
        return new Plugin(
            new HookLoader($config['routes']),
            new HookLoader($config['admin']),
            new MigrationRunner($config['migrations']),
            new EngineRegistry($config['engines']),
            new VersionRegistry($config['versions']),
        );
    }
}
