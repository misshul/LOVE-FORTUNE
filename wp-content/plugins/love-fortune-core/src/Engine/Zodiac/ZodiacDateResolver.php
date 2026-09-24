<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Zodiac;

use LoveFortune\Core\Engine\Saju\GregorianDayNumber;

final class ZodiacDateResolver
{
    /** Pass the original Gregorian birthDate, never an adjusted Saju/UTC date. */
    public function resolve(string $birthDate): array
    {
        (new GregorianDayNumber())->assertPublicDate($birthDate);
        $catalog = ZodiacCatalog::configuration()['catalog'];
        $monthDay = substr($birthDate, 5);
        foreach ($catalog['referenceTables']['signs'] as $row) {
            $matches = $row['start'] <= $row['end']
                ? $monthDay >= $row['start'] && $monthDay <= $row['end']
                : $monthDay >= $row['start'] || $monthDay <= $row['end'];
            if ($matches) {
                return ['sign' => $row['sign'], 'modelVersion' => $catalog['modelVersion'], 'dateRangeVersion' => $catalog['dateRangeVersion']];
            }
        }
        throw new \LogicException('Invalid Zodiac reference coverage.');
    }
}
