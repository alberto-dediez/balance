<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

enum MaterialityAnswerScale: string
{
    case Level = 'level';

    case YesNo = 'yes_no';

    /** @return non-empty-list<int> */
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

    public function labelKey(int $value): string
    {
        if (!in_array($value, $this->values(), true)) {
            throw new \InvalidArgumentException(sprintf('Value "%d" is not part of the "%s" answer scale.', $value, $this->value));
        }

        return sprintf('%s_%d', $this->value, $value);
    }
}
