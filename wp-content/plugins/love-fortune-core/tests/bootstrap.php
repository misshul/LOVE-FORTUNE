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


// Minimal transport doubles; actual WordPress registration is covered by wordpress-public-api.php.
class WP_HTTP_Response {
    public function __construct(private mixed $data=null,private int $status=200,private array $headers=[]) {}
    public function get_data(): mixed { return $this->data; }
    public function get_status(): int { return $this->status; }
    public function get_headers(): array { return $this->headers; }
}
class WP_REST_Response extends WP_HTTP_Response {}
class WP_REST_Server {}
class WP_REST_Request {
    private array $headers=[];private string $body='';private array $query=[];
    public function __construct(private string $method='POST',private string $route='/love-fortune/v1/compatibility/calculate') {}
    public function get_method(): string{return $this->method;}
    public function get_route(): string{return $this->route;}
    public function set_body(string $body): void{$this->body=$body;}
    public function get_body(): string{return $this->body;}
    public function set_header(string $key,string $value): void{$this->headers[strtolower($key)]=$value;}
    public function get_header(string $key): string{return $this->headers[strtolower($key)]??'';}
    public function get_headers(): array{return $this->headers;}
    public function set_query_params(array $query): void{$this->query=$query;}
    public function get_query_params(): array{return $this->query;}
}
function register_rest_route(string $namespace,string $route,array $args): void{$GLOBALS['public_routes'][$namespace.$route]=$args;}
function add_filter(string $name,callable $callback,int $priority=10,int $args=1): void{$GLOBALS['public_filters'][$name][]=$callback;}
