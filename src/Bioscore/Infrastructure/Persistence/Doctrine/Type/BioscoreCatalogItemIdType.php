<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Persistence\Doctrine\Type;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class BioscoreCatalogItemIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'bioscore_catalog_item_id';
    }

    public function getClassName(): string
    {
        return BioscoreCatalogItemId::class;
    }
}
