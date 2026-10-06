<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
final class WeeklyEndpoint extends PeriodEndpoint
{
    public const ROUTE='/love-fortune/v1/fortune/weekly';
    protected const TYPE='WEEK';
}
