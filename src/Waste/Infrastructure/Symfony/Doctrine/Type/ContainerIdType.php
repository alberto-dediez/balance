<?php

namespace App\Waste\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;

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
