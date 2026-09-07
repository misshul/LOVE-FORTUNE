<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

use Throwable;

/** Handles plugin lifecycle failures only; never installs a global error handler. */
final class ErrorHandler
{
    public static function run(callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable) {
            // Do not log exception messages, stack traces, SQL, or request contents.
            error_log('LOVE_FORTUNE_BOOTSTRAP_FAILED');
            wp_die(
                'LOVE FORTUNE could not initialize. (LOVE_FORTUNE_BOOTSTRAP_FAILED)',
                'LOVE FORTUNE',
                ['response' => 500]
            );
        }
    }
}
