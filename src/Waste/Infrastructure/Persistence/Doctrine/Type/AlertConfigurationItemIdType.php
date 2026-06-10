<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use Doctrine\DBAL\Types\Type;

class AlertConfigurationItemIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'alert_configuration_item_id';
    }

    public function getClassName(): string
    {
        return AlertConfigurationItemId::class;
    }
}
