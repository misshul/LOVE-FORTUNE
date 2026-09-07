<?php
/**
 * Plugin Name: LOVE FORTUNE Core
 * Description: Bootstrap and infrastructure for the privacy-first LOVE FORTUNE service.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Text Domain: love-fortune-core
 * Update URI: false
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/autoload.php';

// Register activation immediately: plugins_loaded has already fired during activation.
register_activation_hook(__FILE__, static function (bool $network_wide): void {
    \LoveFortune\Core\Support\ErrorHandler::run(static function () use ($network_wide): void {
        $plugin = \LoveFortune\Core\PluginFactory::create(__DIR__);
        $plugin->activate($network_wide);
    });
});

register_deactivation_hook(__FILE__, static function (bool $network_wide): void {
    \LoveFortune\Core\Support\ErrorHandler::run(static function () use ($network_wide): void {
        \LoveFortune\Core\PluginFactory::create(__DIR__)->deactivate($network_wide);
    });
});

add_action('plugins_loaded', static function (): void {
    \LoveFortune\Core\Support\ErrorHandler::run(static function (): void {
        \LoveFortune\Core\PluginFactory::create(__DIR__)->boot();
    });
});
