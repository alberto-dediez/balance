<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Type;

use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MessageAttachmentIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'assistant_message_attachment_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return MessageAttachmentId::class;
    }
}
