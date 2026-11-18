<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\ConversationAuthorizer;

class ConversationAuthorizerMother
{
    public static function create(): ConversationAuthorizer
    {
        return new ConversationAuthorizer();
    }
}
