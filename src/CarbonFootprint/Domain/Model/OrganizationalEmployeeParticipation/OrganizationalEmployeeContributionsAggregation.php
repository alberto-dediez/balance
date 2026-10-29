<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

final class OrganizationalEmployeeContributionsAggregation
{
    /**
     * @param array<string, array<string, float>> $commutingKmByBlock
     * @param array<string, array<string, float>> $businessTravelKmByBlock
     * @param array<string, float>                $hotelNightsByBlock
     */
    public function __construct(
        private readonly array $commutingKmByBlock,
        private readonly array $businessTravelKmByBlock,
        private readonly array $hotelNightsByBlock,
    ) {}

    /** @return list<string> */
    public function blocks(): array
    {
        $blocks = [];

        foreach ([$this->commutingKmByBlock, $this->businessTravelKmByBlock, $this->hotelNightsByBlock] as $byBlock) {
            foreach (array_keys($byBlock) as $block) {
                if (!in_array((string) $block, $blocks, true)) {
                    $blocks[] = (string) $block;
                }
            }
        }

        return $blocks;
    }

    /** @return array<string, float> */
    public function commutingKmByMobilityId(string $block): array
    {
        return $this->commutingKmByBlock[$block] ?? [];
    }

    /** @return array<string, float> */
    public function businessTravelKmByMobilityId(string $block): array
    {
        return $this->businessTravelKmByBlock[$block] ?? [];
    }

    public function hotelNights(string $block): float
    {
        return $this->hotelNightsByBlock[$block] ?? 0.0;
    }
}
