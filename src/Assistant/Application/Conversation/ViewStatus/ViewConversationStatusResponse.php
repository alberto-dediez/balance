<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ViewStatus;

class ViewConversationStatusResponse
{
    public function __construct(public string $fingerprint, public bool $busy) {}
}
