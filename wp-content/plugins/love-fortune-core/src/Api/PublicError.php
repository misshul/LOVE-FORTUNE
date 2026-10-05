<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

final class PublicError extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $publicCode, public readonly array $headers = [])
    {
        parent::__construct($publicCode);
    }
    public function body(): array
    {
        return ['error'=>['code'=>$this->publicCode,'messageKey'=>'errors.'.strtolower($this->publicCode),'details'=>(object)[]]];
    }
}
