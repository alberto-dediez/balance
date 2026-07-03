<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Doctrine\Type;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ImportationProfileIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'importation_profile_id';
    }

    public function getClassName(): string
    {
        return ImportationProfileId::class;
    }
}
