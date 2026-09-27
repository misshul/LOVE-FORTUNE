<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use RuntimeException;
use Throwable;

/** Internal request-local production orchestration; no API, storage, logs or score semantics. */
final class NatalResolutionService
{
    public const VERSION = 'SAJU_NATAL_RESOLVER_V1';
    private readonly NatalLocalTimeResolver $local;
    private readonly NatalCandidateContextFactory $contexts;
    private readonly NatalCandidatePartitioner $partitioner;

    public function __construct(
        private readonly LocationReferenceProvider $locations = new LocationReferenceRepository(),
        TimezoneReferenceRepository $timezones = new TimezoneReferenceRepository(),
        SolarTermReference $solar = new SolarTermReference(),
    ) {
        $this->local = new NatalLocalTimeResolver($timezones);
        $this->contexts = new NatalCandidateContextFactory($solar);
        $this->partitioner = new NatalCandidatePartitioner($solar);
    }

    public function resolve(string $birthDate, string $birthLocationId, ?string $birthTime = null): array
    {
        $nominal = NatalCivilTime::input($birthDate, $birthTime);
        $location = $this->locations->get($birthLocationId);
        $known = $birthTime !== null;
        $ranges = $this->local->resolve($location['timezoneId'], $nominal, $known);
        $candidates = [];
        try {
            foreach ($ranges as $range) {
                $parts = $known ? [$range] : $this->partitioner->partition($range, $location['longitudeMicrodegrees']);
                foreach ($parts as $part) {
                    $candidates[] = $this->contexts->create($part, $location['longitudeMicrodegrees'], $known);
                }
            }
        } catch (Throwable) {
            // Do not expose raw input, reference paths or downstream exception text/trace.
            throw new RuntimeException('CANDIDATE_RESOLUTION_FAILED');
        }
        if (!$known) {
            $candidates = $this->partitioner->normalize($candidates);
        }
        foreach ($candidates as $i => &$candidate) {
            $candidate['candidateId'] = 'C' . $i;
        }
        unset($candidate);
        return [
            'status' => $candidates === [] ? 'GAP_UNRESOLVED' : (!$known ? 'UNKNOWN_TIME' : (count($candidates) === 1 ? 'UNIQUE' : 'FOLD_AMBIGUOUS')),
            'timeKnown' => $known,
            'locationReferenceVersion' => LocationReferenceRepository::VERSION,
            'timezoneReferenceVersion' => TimezoneReferenceRepository::VERSION,
            'natalResolverVersion' => self::VERSION,
            'referenceProvenance' => ['locationFileChecksum' => LocationReferenceRepository::FILE_SHA256,
                'timezoneFileChecksum' => TimezoneReferenceRepository::FILE_SHA256,
                'locationRecordChecksum' => $location['referenceChecksum']],
            'candidates' => $candidates,
        ];
    }
}
