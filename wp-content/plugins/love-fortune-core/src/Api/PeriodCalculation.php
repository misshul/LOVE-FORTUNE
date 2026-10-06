<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\PeriodOrchestrationService;
final class PeriodCalculation
{
    public function calculate(string $type,array $input,string $id): array
    {
        $base=(new CompatibilityCalculation())->internal($input);
        return PeriodProjection::project((new PeriodOrchestrationService())->calculate($type,$input,$base),$id);
    }
}
