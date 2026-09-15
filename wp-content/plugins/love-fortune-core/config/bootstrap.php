<?php
declare(strict_types=1);

use LoveFortune\Core\Engine\Saju\DayPillarCalculator;
use LoveFortune\Core\Engine\Saju\SajuDayPillarService;

// Code-owned registrations only. No request input or secrets.
$dayPillar = new DayPillarCalculator();
return [
    'routes' => [],
    'admin' => [],
    'migrations' => [],
    'engines' => [
        'saju.day_pillar' => $dayPillar,
        'saju.day_pillar_service' => new SajuDayPillarService($dayPillar),
    ],
    'versions' => ['saju.day_pillar' => DayPillarCalculator::IDENTIFIER],
];
