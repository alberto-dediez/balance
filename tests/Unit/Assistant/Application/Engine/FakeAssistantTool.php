<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolPreview;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\Assistant\WriteAssistantTool;

class FakeAssistantTool implements WriteAssistantTool
{
    /** @var list<ToolArguments> */
    public array $executions = [];

    /** @var list<ToolContext> */
    public array $contexts = [];

    /** @var list<ToolResult|\Throwable> */
    private array $outcomes = [];

    private ?\Closure $onExecute = null;

    /**
     * @param list<ToolParameter>   $parameters
     * @param array<string, string> $displays
     */
    public function __construct(
        private readonly string $name,
        private readonly ToolMode $mode = ToolMode::Read,
        private readonly array $parameters = [],
        private readonly bool $available = true,
        private readonly array $displays = [],
        private readonly ?\Throwable $previewError = null,
    ) {}

    public static function read(string $name, ToolParameter ...$parameters): self
    {
        return new self($name, ToolMode::Read, array_values($parameters));
    }

    public static function write(string $name, ToolParameter ...$parameters): self
    {
        return new self($name, ToolMode::Write, array_values($parameters));
    }

    public function willReturn(ToolResult|\Throwable ...$outcomes): self
    {
        $this->outcomes = [...$this->outcomes, ...array_values($outcomes)];

        return $this;
    }

    public function onExecute(\Closure $callback): self
    {
        $this->onExecute = $callback;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return 'Etiqueta de ' . $this->name;
    }

    public function description(): string
    {
        return 'Descripción de ' . $this->name;
    }

    public function mode(): ToolMode
    {
        return $this->mode;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->available;
    }

    public function preview(ToolArguments $arguments, User $user, ToolContext $context): ToolPreview
    {
        if (null !== $this->previewError) {
            throw $this->previewError;
        }

        return new ToolPreview($this->displays);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $this->executions[] = $arguments;
        $this->contexts[] = $context;

        if (null !== $this->onExecute) {
            ($this->onExecute)();
        }
        $outcome = array_shift($this->outcomes) ?? new ToolResult(sprintf('%s ejecutada', $this->name), ['ok' => true]);

        if ($outcome instanceof \Throwable) {
            throw $outcome;
        }

        return $outcome;
    }
}
