<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\WriteAssistantTool;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class Toolbox
{
    /** @var array<string, AssistantTool> */
    private array $tools = [];

    /** @param iterable<AssistantTool> $tools */
    public function __construct(
        #[AutowireIterator('shared.assistant.tool')]
        iterable $tools,
    ) {
        foreach ($tools as $tool) {
            $name = $tool->name();

            if (isset($this->tools[$name])) {
                throw new DuplicatedToolNameException($name, $this->tools[$name]::class, $tool::class);
            }

            $this->tools[$name] = $tool;
        }

        ksort($this->tools);
    }

    /** @return list<AssistantTool> */
    public function readToolsFor(User $user): array
    {
        return array_values(array_filter(
            $this->tools,
            static fn(AssistantTool $tool): bool => ToolMode::Read === $tool->mode() && $tool->isAvailableTo($user),
        ));
    }

    /** @return list<WriteAssistantTool> */
    public function writeToolsFor(User $user): array
    {
        $tools = [];

        foreach ($this->tools as $tool) {
            if ($tool instanceof WriteAssistantTool && ToolMode::Write === $tool->mode() && $tool->isAvailableTo($user)) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    public function get(string $name): ?AssistantTool
    {
        return $this->tools[$name] ?? null;
    }

    /** @return array<string, string> */
    public function labels(): array
    {
        return array_map(static fn(AssistantTool $tool): string => $tool->label(), $this->tools);
    }

    /** @return array<string, array<string, string>> */
    public function parameterLabels(): array
    {
        $labels = [];

        foreach ($this->tools as $name => $tool) {
            foreach ($tool->parameters() as $parameter) {
                $labels[$name][$parameter->name] = $parameter->label;
            }
        }

        return $labels;
    }
}
