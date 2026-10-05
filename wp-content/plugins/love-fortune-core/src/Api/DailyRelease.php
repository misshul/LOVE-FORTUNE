<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\DailyOrchestrationService;
final class DailyRelease
{
    public const CONFIG='CONFIG_DAILY_V1';
    public static function versions(): array { return array_replace(PublicRelease::versions(),['configVersion'=>self::CONFIG]); }
    public static function manifest(): array { return ['configVersion'=>self::CONFIG,'baseConfig'=>PublicRelease::CONFIG,'baseDependencies'=>PublicRelease::manifest(),'dailyVersion'=>DailyOrchestrationService::VERSION,'dailyDependencies'=>DailyOrchestrationService::dependencies()]; }
}
