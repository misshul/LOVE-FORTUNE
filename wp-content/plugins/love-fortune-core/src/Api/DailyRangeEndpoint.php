<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
final class DailyRangeEndpoint extends PeriodEndpoint
{
    public const ROUTE='/love-fortune/v1/fortune/daily-range';
    protected const TYPE='RANGE';
}
