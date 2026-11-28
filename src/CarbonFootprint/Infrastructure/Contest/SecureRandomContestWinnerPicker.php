<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Contest;

use App\CarbonFootprint\Domain\Model\Contest\ContestWinnerPicker;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class SecureRandomContestWinnerPicker implements ContestWinnerPicker
{
    /**
     * @param list<ContestEntryId> $candidates
     *
     * @return list<ContestEntryId>
     */
    public function pick(array $candidates, int $count): array
    {
        if ($count <= 0 || [] === $candidates) {
            return [];
        }

        $randomizer = new \Random\Randomizer(new \Random\Engine\Secure());

        return array_slice($randomizer->shuffleArray($candidates), 0, $count);
    }
}
