<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\Conversation\ConversationRepository;

class InMemoryConversationRepositoryTest extends ConversationRepositoryTest
{
    protected function createSUT(): ConversationRepository
    {
        return ConversationRepositoryMother::inMemory();
    }
}
