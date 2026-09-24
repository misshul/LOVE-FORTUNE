<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use LoveFortune\Core\Database\MigrationInterface;
use LoveFortune\Core\Database\MigrationRunner;
use LoveFortune\Core\Engine\EngineRegistry;
use LoveFortune\Core\PluginFactory;
use LoveFortune\Core\Support\ConfigLoader;
use LoveFortune\Core\Support\ErrorHandler;
use LoveFortune\Core\Support\HookLoader;
use LoveFortune\Core\Support\RegistrationInterface;
use LoveFortune\Core\Support\VersionRegistry;

final class BootstrapTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['hooks'] = [];
        $GLOBALS['options'] = [];
        $GLOBALS['writes'] = 0;
    }

    public function testNamespaceIsolationAndMissingClass(): void
    {
        check(!class_exists('OtherVendor\\Missing'), 'Foreign namespace must be ignored.');
        check(!class_exists('LoveFortune\\Core\\Missing'), 'Missing class must be ignored.');
        check(class_exists(PluginFactory::class), 'Plugin must autoload.');
    }

    public function testEmptyBootstrapHasNoPersistenceAndRegistersHooksOnce(): void
    {
        $before = $GLOBALS['writes'];
        $plugin = PluginFactory::create(dirname(__DIR__, 2));
        $plugin->activate();
        $plugin->activate(true);
        $plugin->boot();
        $plugin->boot();
        foreach (['rest_api_init', 'admin_menu'] as $hook) {
            check(count($GLOBALS['hooks'][$hook]) === 1, 'Duplicate lifecycle hook.');
            ($GLOBALS['hooks'][$hook][0])();
        }
        check($before === $GLOBALS['writes'], 'Empty bootstrap must not write options.');
        check($plugin->versions->get('saju.day_pillar') === 'SAJU_DAY_PILLAR_EPOCH_V1', 'Approved epoch version.');
        check($plugin->versions->get('zodiacDateRangeVersion') === 'ZODIAC_DATE_RANGE_V1', 'Approved Zodiac version.');
        check($plugin->engines->get('zodiac.date')->resolve('2000-02-29')['sign'] === 'PISCES', 'Registered Zodiac resolver.');
        check(!$plugin->engines->has('astrology'), 'Advanced Astrology must not activate in V1.');
        check($plugin->engines->get('saju.day_pillar')->calculate('2019-01-27')['ganzhi'] === '甲子', 'Registered production calculator.');
        check(!$plugin->engines->has('saju'), 'No fake engine.');
    }

    public function testConfigFailsClosedForMissingAndMalformedData(): void
    {
        $loader = new ConfigLoader();
        expectException(fn () => $loader->load(__DIR__ . '/absent.php'), UnexpectedValueException::class);
        $file = tempnam(sys_get_temp_dir(), 'lf_config_');
        try {
            foreach (['return null;', 'return [];', "return ['routes' => 'invalid'];"] as $code) {
                file_put_contents($file, '<?php ' . $code);
                expectException(fn () => $loader->load($file), UnexpectedValueException::class);
            }
        } finally {
            unlink($file);
        }
    }

    public function testEntryPointDefersBootAndPermitsSilentActivation(): void
    {
        define('ABSPATH', '/test-wordpress/');
        $saved = $GLOBALS['hooks'];
        $GLOBALS['hooks'] = [];
        $before = $GLOBALS['writes'];
        ob_start();
        try {
            require dirname(__DIR__, 2) . '/love-fortune-core.php';
            check(isset($GLOBALS['activation_callback']), 'Activation callback missing.');
            check(!isset($GLOBALS['hooks']['rest_api_init']), 'Boot must wait for plugins_loaded.');
            ($GLOBALS['activation_callback'])(false);
            check(isset($GLOBALS['deactivation_callback']), 'Deactivation callback missing.');
            ($GLOBALS['deactivation_callback'])(false);
            ($GLOBALS['hooks']['plugins_loaded'][0])();
            check(isset($GLOBALS['hooks']['rest_api_init']), 'REST loader hook missing.');
            check(isset($GLOBALS['hooks']['admin_menu']), 'Admin loader hook missing.');
            check($GLOBALS['writes'] === $before, 'Activation must not persist data.');
            check(ob_get_contents() === '', 'Activation must not output content.');
        } finally {
            ob_end_clean();
            $GLOBALS['hooks'] = $saved;
        }
    }

    public function testRouteAdminRegistrarDispatchIsIdempotent(): void
    {
        $module = new class implements RegistrationInterface {
            public int $calls = 0;
            public function register(): void { ++$this->calls; }
        };
        $loader = new HookLoader([$module]);
        $loader->load();
        $loader->load();
        check($module->calls === 1, 'Registrar must run once.');
        expectException(fn () => new HookLoader([new stdClass()]), InvalidArgumentException::class);
    }

    public function testRegistriesRejectInvalidAndUnknownEntries(): void
    {
        $engine = new stdClass();
        check((new EngineRegistry(['test' => $engine]))->get('test') === $engine, 'Engine identity changed.');
        check((new VersionRegistry(['test' => '1.2.3']))->get('test') === '1.2.3', 'Version mismatch.');
        expectException(fn () => (new EngineRegistry([]))->get('unknown'), OutOfBoundsException::class);
        expectException(fn () => (new VersionRegistry([]))->get('unknown'), OutOfBoundsException::class);
        expectException(fn () => new EngineRegistry(['test' => null]), InvalidArgumentException::class);
        expectException(fn () => new VersionRegistry(['test' => '']), InvalidArgumentException::class);
    }

    public function testMigrationJournalRetryLockAndDuplicateIDs(): void
    {
        $migration = new class implements MigrationInterface {
            public int $calls = 0;
            public bool $fail = true;
            public function id(): string { return 'unit-test-migration'; }
            public function up(): void {
                ++$this->calls;
                if ($this->fail) { throw new RuntimeException('Synthetic failure'); }
            }
        };
        $runner = new MigrationRunner([$migration]);
        expectException(fn () => $runner->run(), RuntimeException::class);
        check(!isset($GLOBALS['options']['love_fortune_migration_lock']), 'Failure must release lock.');
        check(!isset($GLOBALS['options']['love_fortune_completed_migrations']), 'Failure must not be marked complete.');
        $migration->fail = false;
        $runner->run();
        $runner->run();
        check($migration->calls === 2, 'Completed migration must not rerun.');
        expectException(fn () => new MigrationRunner([$migration, $migration]), InvalidArgumentException::class);
        expectException(fn () => $runner->run(true), RuntimeException::class);
        $GLOBALS['options']['love_fortune_migration_lock'] = 'running';
        expectException(fn () => $runner->run(), RuntimeException::class);
        check(isset($GLOBALS['options']['love_fortune_migration_lock']), 'Do not release another execution lock.');
        unset($GLOBALS['options']['love_fortune_migration_lock']);
    }

    public function testLifecycleErrorExcludesExceptionDetails(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'lf_log_');
        $previous = ini_get('error_log');
        ini_set('error_log', $file);
        try {
            try {
                ErrorHandler::run(static function (): void { throw new RuntimeException('SENSITIVE_TEST_SENTINEL'); });
                throw new LogicException('Handler did not fail.');
            } catch (RuntimeException $error) {
                check($error->getCode() === 500, 'Expected generic failure status.');
                check(!str_contains($error->getMessage(), 'SENSITIVE_TEST_SENTINEL'), 'Exception detail leaked.');
            }
            $log = file_get_contents($file);
            check(str_contains($log, 'LOVE_FORTUNE_BOOTSTRAP_FAILED'), 'Missing safe error code.');
            check(!str_contains($log, 'SENSITIVE_TEST_SENTINEL'), 'Log leaked exception data.');
        } finally {
            ini_set('error_log', $previous);
            unlink($file);
        }
    }

}
