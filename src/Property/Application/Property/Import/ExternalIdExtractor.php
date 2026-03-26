<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Import;

use App\Property\Domain\Model\Property\ExternalId;

class ExternalIdExtractor
{
    /**
     * @param array<mixed> $row
     */
    public function extract(FieldExtractor $fieldExtractor, array $row): ExternalId
    {
        $restel = $fieldExtractor->extract(Field::ExternalRestel, $row);
        $bioscore = $fieldExtractor->extract(Field::ExternalBioscore, $row);
        $hotelbeds = $fieldExtractor->extract(Field::ExternalHotelbeds, $row);

        return new ExternalId(
            hotelbeds: (null !== $hotelbeds && !empty($hotelbeds)) ? $hotelbeds : null,
            restel: (null !== $restel && !empty($restel)) ? $restel : null,
            bioscore: (null !== $bioscore && !empty($bioscore)) ? $bioscore : null,
        );
    }
}
