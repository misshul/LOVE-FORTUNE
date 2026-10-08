<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
abstract class PeriodEndpoint extends CompatibilityEndpoint
{
    protected const TYPE='';
    protected function rateDirectory(): string{return strtolower(static::TYPE);}
    protected function parseInput(string $body,string $type,?string $encoding): array{return PeriodInput::parse(static::TYPE,$body,$type,$encoding);}
    protected function calculateResult(array $input,string $id): array{return (new PeriodCalculation())->calculate(static::TYPE,$input,$id);}
    protected function validateResult(array $r): void{PeriodResultValidation::validate($r,static::TYPE);}
    protected function needsSignature(array $r): bool{return PeriodResultValidation::needsSignature($r);}
}
