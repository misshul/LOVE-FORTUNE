<?php
declare(strict_types=1);

// A local namespace loader keeps bootstrap independent of a vendor installation.
spl_autoload_register(static function (string $class): void {
    $prefix = 'LoveFortune\\Core\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $relative)) {
        return;
    }

    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
