<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Contest;

use App\CarbonFootprint\Domain\Model\Contest\ContestWinnerPicker;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContestWinnerPicker implements ContestWinnerPicker
{
    /**
     * @param list<ContestEntryId> $candidates
     *
     * @return list<ContestEntryId>
     */
    public function pick(array $candidates, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        return array_slice($candidates, 0, $count);
    }
}
