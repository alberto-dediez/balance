<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\Branding\BrandingId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class BrandingIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'branding_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return BrandingId::class;
    }
}
