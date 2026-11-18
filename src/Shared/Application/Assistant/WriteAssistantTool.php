<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

use App\Security\Domain\Model\User\User;

interface WriteAssistantTool extends AssistantTool
{
    public function preview(ToolArguments $arguments, User $user, ToolContext $context): ToolPreview;
}
