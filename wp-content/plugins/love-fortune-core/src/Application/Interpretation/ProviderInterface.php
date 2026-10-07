<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

interface ProviderInterface
{
    /** Configured, non-personal provider/model IDs; never taken from request data. */
    public function identity(): array;
    /** Implementations MUST enforce the supplied timeout; no logging or automatic retries. */
    public function generate(array $dto, float $timeout, array $errors=[]): string;
}
