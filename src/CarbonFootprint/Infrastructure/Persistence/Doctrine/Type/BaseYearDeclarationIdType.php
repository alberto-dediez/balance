<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class BaseYearDeclarationIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'base_year_declaration_id';
    }

    public function getClassName(): string
    {
        return BaseYearDeclarationId::class;
    }
}
