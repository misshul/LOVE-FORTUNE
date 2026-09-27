<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** Internal dependency seam. Production wiring always uses the pinned repository. */
interface LocationReferenceProvider
{
    public function get(string $locationId): array;
}
