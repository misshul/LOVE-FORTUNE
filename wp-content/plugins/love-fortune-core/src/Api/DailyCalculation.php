<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\DailyOrchestrationService;
final class DailyCalculation
{
    public function calculate(array $input,string $id): array
    {
        $base=(new CompatibilityCalculation())->internal($input);
        $daily=(new DailyOrchestrationService())->calculate($base['combined'],$base['natal'][0],$base['natal'][1],$input['date'],$input['targetTimezone']);
        return DailyProjection::project($daily,$id);
    }
}
