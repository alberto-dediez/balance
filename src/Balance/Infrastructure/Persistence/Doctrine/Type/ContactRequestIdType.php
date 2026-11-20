<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ContactRequestIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'contact_request_id';
    }

    public function getClassName(): string
    {
        return ContactRequestId::class;
    }
}
