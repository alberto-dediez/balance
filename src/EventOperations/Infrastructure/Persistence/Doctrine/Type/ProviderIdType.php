<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\Doctrine\Type;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProviderIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'provider_id';
    }

    public function getClassName(): string
    {
        return ProviderId::class;
    }
}
