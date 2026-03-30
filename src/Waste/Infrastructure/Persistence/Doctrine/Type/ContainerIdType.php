<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\Container\ContainerId;
use Doctrine\DBAL\Types\Type;

class ContainerIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'container_id';
    }

    public function getClassName(): string
    {
        return ContainerId::class;
    }
}
