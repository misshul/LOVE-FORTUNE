<?php
declare(strict_types=1);

namespace LoveFortune\Core\Domain\Score;

use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;
use LoveFortune\Core\Support\Rational as R;

final class DailyAggregator
{
    /** Four nominal slots; null is unavailable. Slot order survives DST omissions. */
    public function aggregate(array $slots): array
    {
        if (count($slots) !== 4 || !array_is_list($slots)) { throw new \InvalidArgumentException('Expected four nominal slots.'); }
        $sum = $confidence = R::of(0);
        $peak = null;
        $peakIndex = null;
        $count = 0;
        foreach ($slots as $index => $slot) {
            if ($slot === null) { continue; }
            if (($slot['source'] ?? null) !== 'SAJU' || !($slot['signal'] ?? null) instanceof R || !($slot['confidence'] ?? null) instanceof R
                || $slot['signal']->abs()->compare(R::of(1)) > 0 || $slot['confidence']->compare(R::of(0)) < 0 || $slot['confidence']->compare(R::of(1)) > 0) {
                throw new \InvalidArgumentException('Invalid Saju daily sample.');
            }
            ++$count;
            $sum = $sum->add($slot['signal']);
            $confidence = $confidence->add($slot['confidence']);
            if ($peak === null || $slot['signal']->abs()->compare($peak->abs()) > 0) {
                $peak = $slot['signal'];
                $peakIndex = $index;
            }
        }
        $scope = ZodiacCatalog::configuration()['scope'];
        $signal = $count === 0 ? R::of(0) : $sum->divide(R::of($count))->multiply(R::of($scope['meanWeight']))->add($peak->multiply(R::of($scope['peakWeight'])));
        return ['signal' => $signal, 'confidence' => $confidence->divide(R::of(4)), 'peakIndex' => $peakIndex, 'sampleCount' => $count];
    }

    /** Nullable baseline preserves Lifetime COMMUNICATION unavailability. */
    public function score(?R $baseline, R $signal): ?R
    {
        if ($signal->abs()->compare(R::of(1)) > 0) { throw new \InvalidArgumentException('Invalid daily signal.'); }
        return $baseline?->add(R::of(ZodiacCatalog::configuration()['scope']['dailyScale'])->multiply($signal))->clamp(R::of(0), R::of(100));
    }
}
