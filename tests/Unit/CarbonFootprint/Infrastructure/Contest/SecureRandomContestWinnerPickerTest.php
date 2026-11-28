<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Contest;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Infrastructure\Contest\SecureRandomContestWinnerPicker;
use PHPUnit\Framework\TestCase;

class SecureRandomContestWinnerPickerTest extends TestCase
{
    public function testPicksDistinctCandidates(): void
    {
        $candidates = array_map(static fn(int $i): ContestEntryId => new ContestEntryId('entry-' . $i), range(1, 10));
        $candidateIds = array_map(static fn(ContestEntryId $id): string => (string) $id, $candidates);

        $picked = array_map(static fn(ContestEntryId $id): string => (string) $id, (new SecureRandomContestWinnerPicker())->pick($candidates, 4));

        $this->assertCount(4, $picked);
        $this->assertCount(4, array_unique($picked));
        $this->assertSame([], array_diff($picked, $candidateIds));
    }

    public function testPicksAtMostTheCandidates(): void
    {
        $picked = (new SecureRandomContestWinnerPicker())->pick([new ContestEntryId('a'), new ContestEntryId('b')], 5);

        $this->assertCount(2, $picked);
    }

    public function testPicksNothingWithoutCandidates(): void
    {
        $this->assertSame([], (new SecureRandomContestWinnerPicker())->pick([], 3));
    }
}
