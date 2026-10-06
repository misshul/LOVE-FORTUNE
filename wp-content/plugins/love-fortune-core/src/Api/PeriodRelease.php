<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\PeriodOrchestrationService;
final class PeriodRelease
{
    public const CONFIG='CONFIG_PERIOD_V1';
    public static function versions(): array{return array_replace(PublicRelease::versions(),['configVersion'=>self::CONFIG]);}
    public static function manifest(): array{return ['configVersion'=>self::CONFIG,'base'=>DailyRelease::manifest(),'periodVersion'=>PeriodOrchestrationService::VERSION,'dependencies'=>PeriodOrchestrationService::dependencies()];}
}
