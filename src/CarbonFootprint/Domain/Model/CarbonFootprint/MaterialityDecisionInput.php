<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

final readonly class MaterialityDecisionInput
{
    public function __construct(
        public string $topicId,
        public ?int $topicNumber,
        public float $scorePercent,
        public int $thresholdPercent,
        public bool $forcedByHighMagnitude,
        public MaterialityVerdict $verdict,
    ) {}

    /** @return array{topicId: string, topicNumber: int|null, scorePercent: float, thresholdPercent: int, forcedByHighMagnitude: bool, verdict: string} */
    public function toPrimitives(): array
    {
        return [
            'topicId' => $this->topicId,
            'topicNumber' => $this->topicNumber,
            'scorePercent' => $this->scorePercent,
            'thresholdPercent' => $this->thresholdPercent,
            'forcedByHighMagnitude' => $this->forcedByHighMagnitude,
            'verdict' => $this->verdict->value,
        ];
    }

    /** @param array{topicId: string, topicNumber?: int|null, scorePercent: float|int|string, thresholdPercent: int, forcedByHighMagnitude?: bool, verdict: string} $primitives */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            topicId: $primitives['topicId'],
            topicNumber: $primitives['topicNumber'] ?? null,
            scorePercent: (float) $primitives['scorePercent'],
            thresholdPercent: (int) $primitives['thresholdPercent'],
            forcedByHighMagnitude: (bool) ($primitives['forcedByHighMagnitude'] ?? false),
            verdict: MaterialityVerdict::from($primitives['verdict']),
        );
    }
}
