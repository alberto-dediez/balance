<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;

final class CatalogTargetNotFoundException extends \RuntimeException
{
    public function __construct(BioscoreCatalog $catalog, string $targetId)
    {
        parent::__construct(sprintf('No target "%s" found for the Bioscore catalog "%s".', $targetId, $catalog->value));
    }
}
