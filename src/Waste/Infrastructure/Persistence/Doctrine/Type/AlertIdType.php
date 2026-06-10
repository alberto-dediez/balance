<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\Alert\AlertId;
use Doctrine\DBAL\Types\Type;

class AlertIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'alert_id';
    }

    public function getClassName(): string
    {
        return AlertId::class;
    }
}
