<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** Each internal array owns one joint Y/M/D/H result. Never serialize directly to public API. */
final class NatalCandidateContextFactory
{
    private readonly YearPillarCalculator $years;
    private readonly MonthPillarCalculator $months;

    public function __construct(SolarTermReference $solar = new SolarTermReference())
    {
        $this->years = new YearPillarCalculator($solar);
        $this->months = new MonthPillarCalculator($solar);
    }

    public function create(array $range, int $longitudeMicrodegrees, bool $known): array
    {
        $offset = $range['historicalOffsetSeconds'];
        $adjustment = NatalCivilTime::adjustment($longitudeMicrodegrees, $offset);
        $shift = ($offset + $adjustment) * 1000000;
        // For INTERVAL only, the lower endpoint is an internal invariant probe, never a birth time.
        $adjusted = NatalCivilTime::datetime($range['startUs'] + $shift);
        $date = (new CalculationDateResolver())->fromAdjusted($adjusted);
        $day = ['calculationDate' => $date, 'pillar' => (new DayPillarCalculator())->calculate($date)];
        $hours = new HourPillarCalculator();
        return [
            'kind' => $known ? 'POINT' : 'INTERVAL',
            'serviceCoordinateUs' => $known ? (string) $range['startUs'] : null,
            'serviceRange' => $known ? null : ['startUs' => (string) $range['startUs'], 'endUs' => (string) $range['endUs']],
            'historicalOffsetSeconds' => $offset,
            'adjustmentSeconds' => $adjustment,
            'adjustedCivil' => $known ? $adjusted->format('Y-m-d\TH:i:s.u') : null,
            'adjustedCivilRange' => $known ? null : [
                'start' => $adjusted->format('Y-m-d\TH:i:s.u'),
                'end' => NatalCivilTime::datetime($range['endUs'] + $shift)->format('Y-m-d\TH:i:s.u'),
            ],
            'yearPillar' => $this->years->calculate((string) $range['startUs']),
            'monthPillar' => $this->months->calculate((string) $range['startUs']),
            'dayPillar' => $day,
            'hourPillar' => $known ? $hours->calculate($adjusted, $day, HourPillarCalculator::conventions()) : $hours->unknown(),
            'provenance' => ['transitionIdentity' => $range['transitionIdentity'], 'isDst' => $range['isDst'],
                'correctionVersion' => 'KOREAN_LONGITUDE_2330', 'correctionApplications' => 1],
        ];
    }
}
