<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
final class DailyEndpoint extends CompatibilityEndpoint
{
    public const ROUTE='/love-fortune/v1/fortune/daily';
    protected function calculateResult(array $input,string $id): array { return (new DailyCalculation())->calculate($input,$id); }
    protected function validateResult(array $result): void { DailyResultValidation::validate($result); }
    protected function needsSignature(array $result): bool { return DailyResultValidation::needsSignature($result); }
}
