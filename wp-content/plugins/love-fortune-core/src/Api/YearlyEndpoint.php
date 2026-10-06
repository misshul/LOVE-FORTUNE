<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
final class YearlyEndpoint extends PeriodEndpoint
{
    public const ROUTE='/love-fortune/v1/fortune/yearly';
    protected const TYPE='YEAR';
}
