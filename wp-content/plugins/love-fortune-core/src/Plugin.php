<?php
declare(strict_types=1);

namespace LoveFortune\Core;

use LoveFortune\Core\Database\MigrationRunner;
use LoveFortune\Core\Engine\EngineRegistry;
use LoveFortune\Core\Support\ErrorHandler;
use LoveFortune\Core\Support\HookLoader;
use LoveFortune\Core\Support\VersionRegistryInterface;

final class Plugin
{
    private bool $booted = false;

    public function __construct(
        private readonly HookLoader $routes,
        private readonly HookLoader $admin,
        private readonly MigrationRunner $migrations,
        public readonly EngineRegistry $engines,
        public readonly VersionRegistryInterface $versions,
    ) {
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        add_action('rest_api_init', fn () => ErrorHandler::run([$this->routes, 'load']));
        add_action('admin_menu', fn () => ErrorHandler::run([$this->admin, 'load']));
        $this->booted = true;
    }

    public function activate(bool $network_wide = false): void
    {
        $this->migrations->run($network_wide);
    }

    public function deactivate(bool $network_wide = false): void
    {
        // T01 owns no cron jobs, rewrite rules, or persistent resources to clean up.
        // Deactivation must preserve site data.
    }
}
