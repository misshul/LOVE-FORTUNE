<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// WordPress test doubles exist only in the PHPUnit CLI process.
$hooks = [];
$options = [];
$writes = 0;
function add_action(string $hook, callable $callback): void
{
    $GLOBALS['hooks'][$hook][] = $callback;
}
function register_activation_hook(string $file, callable $callback): void
{
    $GLOBALS['activation_callback'] = $callback;
}
function register_deactivation_hook(string $file, callable $callback): void
{
    $GLOBALS['deactivation_callback'] = $callback;
}
function add_option(string $key, mixed $value, mixed $deprecated = '', bool $autoload = false): bool
{
    if (array_key_exists($key, $GLOBALS['options'])) {
        return false;
    }
    $GLOBALS['options'][$key] = $value;
    ++$GLOBALS['writes'];
    return true;
}
function get_option(string $key, mixed $default = false): mixed
{
    return $GLOBALS['options'][$key] ?? $default;
}
function update_option(string $key, mixed $value, bool $autoload = false): bool
{
    $GLOBALS['options'][$key] = $value;
    ++$GLOBALS['writes'];
    return true;
}
function delete_option(string $key): bool
{
    unset($GLOBALS['options'][$key]);
    return true;
}
function is_multisite(): bool
{
    return false;
}
function wp_die(string $message, string $title = '', array $args = []): never
{
    throw new RuntimeException($message, $args['response'] ?? 0);
}
function check(bool $condition, string $message): void
{
    \PHPUnit\Framework\Assert::assertTrue($condition, $message);
}
function expectException(callable $operation, string $type): void
{
    try {
        $operation();
    } catch (Throwable $error) {
        check($error instanceof $type, 'Unexpected exception type.');
        return;
    }
    throw new RuntimeException('Expected exception was not raised.');
}

require dirname(__DIR__) . '/autoload.php';

