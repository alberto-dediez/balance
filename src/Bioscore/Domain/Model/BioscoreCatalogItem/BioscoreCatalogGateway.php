<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

interface BioscoreCatalogGateway
{
    /**
     * @return list<BioscoreCatalogEntry>
     *
     * @throws BioscoreUnavailableException
     */
    public function fetch(BioscoreCatalog $catalog): array;
}
