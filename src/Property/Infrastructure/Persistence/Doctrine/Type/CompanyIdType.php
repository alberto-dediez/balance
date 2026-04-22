<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\Company\CompanyId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CompanyIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'company_id';
    }

    public function getClassName(): string
    {
        return CompanyId::class;
    }
}
