<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

final class BioscoreCatalogItemNotFoundException extends \RuntimeException
{
    public function __construct(BioscoreCatalogItemId $itemId)
    {
        parent::__construct(sprintf('Bioscore catalog item "%s" not found.', (string) $itemId));
    }
}
