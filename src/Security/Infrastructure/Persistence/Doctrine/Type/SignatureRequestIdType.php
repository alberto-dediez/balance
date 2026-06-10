<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class SignatureRequestIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'signature_request_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return SignatureRequestId::class;
    }
}
