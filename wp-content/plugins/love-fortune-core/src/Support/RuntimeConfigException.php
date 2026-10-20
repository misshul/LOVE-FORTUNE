<?php
declare(strict_types=1);
namespace LoveFortune\Core\Support;

final class RuntimeConfigException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('LOVE_FORTUNE_CONFIG_INVALID');
    }
}
