<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Type;

use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MessageIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'assistant_message_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return MessageId::class;
    }
}
