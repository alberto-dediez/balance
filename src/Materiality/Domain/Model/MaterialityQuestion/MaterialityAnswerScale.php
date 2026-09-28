<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

enum MaterialityAnswerScale: string
{
    case Level = 'level';

    case YesNo = 'yes_no';

    /** @return list<int> */
    public function values(): array
    {
        return match ($this) {
            self::Level => [1, 2, 3],
            self::YesNo => [1, 3],
        };
    }

    public function max(): int
    {
        return 3;
    }
}
