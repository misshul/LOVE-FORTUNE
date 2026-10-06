<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
final class MonthlyEndpoint extends PeriodEndpoint
{
    public const ROUTE='/love-fortune/v1/fortune/monthly';
    protected const TYPE='MONTH';
}
