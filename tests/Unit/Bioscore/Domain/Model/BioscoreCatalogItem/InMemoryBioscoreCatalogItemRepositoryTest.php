<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;

class InMemoryBioscoreCatalogItemRepositoryTest extends BioscoreCatalogItemRepositoryTest
{
    protected function createSUT(): BioscoreCatalogItemRepository
    {
        return BioscoreCatalogItemRepositoryMother::inMemory();
    }
}
