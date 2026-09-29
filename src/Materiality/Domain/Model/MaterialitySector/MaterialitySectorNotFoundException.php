<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

final class MaterialitySectorNotFoundException extends \RuntimeException
{
    public function __construct(MaterialitySectorId $materialitySectorId)
    {
        parent::__construct(sprintf('Materiality sector "%s" not found.', (string) $materialitySectorId));
    }
}
