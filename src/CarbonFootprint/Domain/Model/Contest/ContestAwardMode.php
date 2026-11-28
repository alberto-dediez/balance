<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

enum ContestAwardMode: string
{
    case Draw = 'draw';

    case FirstEntrants = 'first_entrants';

    case Everyone = 'everyone';

    public function isDraw(): bool
    {
        return self::Draw === $this;
    }

    public function isFirstEntrants(): bool
    {
        return self::FirstEntrants === $this;
    }

    public function isEveryone(): bool
    {
        return self::Everyone === $this;
    }

    public function needsWinnersCount(): bool
    {
        return self::Everyone !== $this;
    }
}
