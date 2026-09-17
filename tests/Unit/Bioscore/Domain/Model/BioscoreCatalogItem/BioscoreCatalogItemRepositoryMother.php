<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;

class BioscoreCatalogItemRepositoryMother
{
    public static function inMemory(): InMemoryBioscoreCatalogItemRepository
    {
        return new InMemoryBioscoreCatalogItemRepository();
    }
}
