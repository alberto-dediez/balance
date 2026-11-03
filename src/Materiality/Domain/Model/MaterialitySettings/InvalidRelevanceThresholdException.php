<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

final class InvalidRelevanceThresholdException extends \InvalidArgumentException
{
    public function __construct(int $thresholdPercent)
    {
        parent::__construct(sprintf('Relevance threshold "%d" must be between 1 and 100.', $thresholdPercent));
    }
}
