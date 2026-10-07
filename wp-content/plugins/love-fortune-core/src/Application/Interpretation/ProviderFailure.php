<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

/** Contains only a safe classification, never a provider response or exception chain. */
final class ProviderFailure extends \RuntimeException
{
    public function __construct(public readonly string $kind) { parent::__construct('PROVIDER_UNAVAILABLE'); }
    public function retryable(): bool { return in_array($this->kind,['NETWORK','SERVER'],true); }
}
