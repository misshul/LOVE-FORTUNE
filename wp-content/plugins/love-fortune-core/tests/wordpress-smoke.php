<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Explicit local integration check. --cycle exercises both hooks and leaves the plugin active.
define('WP_ADMIN', true);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
    echo 'PASS: ', $message, PHP_EOL;
}

verify(wp_get_environment_type() === 'local', 'Local environment');
verify(is_blog_installed(), 'WordPress installed');
$plugin_file = 'love-fortune-core/love-fortune-core.php';
$plugins = get_plugins();
verify(isset($plugins[$plugin_file]), 'Plugin discovered by WordPress');
verify($plugins[$plugin_file]['Name'] === 'LOVE FORTUNE Core', 'Plugin metadata');

$tables_before = $wpdb->get_col('SHOW TABLES');
$option_pattern = $wpdb->esc_like('love_fortune_') . '%';
$options_before = $wpdb->get_results($wpdb->prepare(
    "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
    $option_pattern
), ARRAY_A);

if (in_array('--cycle', $argv, true)) {
    deactivate_plugins($plugin_file);
    verify(!is_plugin_active($plugin_file), 'Deactivation');
    $result = activate_plugin($plugin_file);
    verify(!is_wp_error($result), 'Activation without errors');
}
verify(is_plugin_active($plugin_file), 'Plugin remains active');
verify($tables_before === $wpdb->get_col('SHOW TABLES'), 'No table changes');
$options_after = $wpdb->get_results($wpdb->prepare(
    "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
    $option_pattern
), ARRAY_A);
verify($options_before === $options_after, 'No plugin option changes');

// Render the actual Core list-table with an existing operator identity, in this CLI process only.
$operators = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
verify($operators !== [], 'Existing administrator available');
wp_set_current_user((int) $operators[0]);
$admin_url = wp_parse_url(admin_url('plugins.php'));
$_SERVER['HTTP_HOST'] = $admin_url['host'] . (isset($admin_url['port']) ? ':' . $admin_url['port'] : '');
$_SERVER['REQUEST_URI'] = $admin_url['path'];
$GLOBALS['pagenow'] = 'plugins.php';
set_current_screen('plugins');
$table = _get_list_table('WP_Plugins_List_Table', ['screen' => 'plugins']);
$table->prepare_items();
ob_start();
$table->display();
$html = ob_get_clean();
$pattern = '~<tr\b[^>]*data-plugin="' . preg_quote($plugin_file, '~') . '"[^>]*>.*?</tr>~s';
verify(preg_match($pattern, $html, $match) === 1, 'Plugin row rendered in admin list');
verify(str_contains($match[0], 'LOVE FORTUNE Core') && str_contains($match[0], 'action=deactivate'), 'Admin row shows active plugin');

$routes = rest_get_server()->get_routes();
verify(array_filter(array_keys($routes), static fn (string $route): bool => str_starts_with($route, '/love-fortune/')) === [], 'No business REST routes');
echo 'WordPress smoke check completed.', PHP_EOL;
