<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

use UnexpectedValueException;

final class ConfigLoader
{
    /** @return array<string, array> */
    public function load(string $file): array
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new UnexpectedValueException('Bootstrap configuration is unavailable.');
        }

        $config = require $file;
        $keys = ['routes', 'admin', 'migrations', 'engines', 'versions'];
        if (!is_array($config) || array_diff(array_keys($config), $keys) !== []) {
            throw new UnexpectedValueException('Invalid bootstrap configuration.');
        }
        foreach ($keys as $key) {
            if (!isset($config[$key]) || !is_array($config[$key])) {
                throw new UnexpectedValueException('Invalid bootstrap registration list.');
            }
        }
        return $config;
    }
}
