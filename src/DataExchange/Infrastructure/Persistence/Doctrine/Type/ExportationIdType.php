<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Doctrine\Type;

use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ExportationIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'exportation_id';
    }

    public function getClassName(): string
    {
        return ExportationId::class;
    }
}
