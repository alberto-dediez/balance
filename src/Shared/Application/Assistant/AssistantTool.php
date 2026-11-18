<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.assistant.tool')]
interface AssistantTool
{
    public function name(): string;

    public function label(): string;

    public function description(): string;

    public function mode(): ToolMode;

    /** @return list<ToolParameter> */
    public function parameters(): array;

    public function isAvailableTo(User $user): bool;

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult;
}
