<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

final class MarginOfError
{
    public static function calculate(int $n, int $N, ConfidenceLevel $level): float
    {
        if ($n <= 0 || $N <= 1 || $n >= $N) {
            return 0.0;
        }

        $z = $level->zScore();

        return $z * sqrt(0.25 / $n) * sqrt(($N - $n) / ($N - 1));
    }
}
