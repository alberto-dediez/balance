<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;

trait GetBioscoreCatalogItemFromIdTrait
{
    private function getBioscoreCatalogItemFromId(string $itemId, BioscoreCatalogItemRepository $repository): BioscoreCatalogItem
    {
        $item = $repository->find(new BioscoreCatalogItemId($itemId));

        if (null === $item) {
            throw $this->createNotFoundException();
        }

        return $item;
    }
}
