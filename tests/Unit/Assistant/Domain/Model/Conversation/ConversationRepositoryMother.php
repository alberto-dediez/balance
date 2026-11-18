<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\Conversation;

use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;

class ConversationRepositoryMother
{
    public static function inMemory(): InMemoryConversationRepository
    {
        return new InMemoryConversationRepository();
    }
}
