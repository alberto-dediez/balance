<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;

class CommandBusDispatcher
{
    protected static ?CommandBusDispatcher $instance = null;

    /**
     * @var CommandHandler[]
     */
    protected array $handlers = [];

    private function __construct() {}

    public static function instance(): CommandBusDispatcher
    {
        if (!static::$instance instanceof CommandBusDispatcher) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function __clone()
    {
        throw new \BadMethodCallException('Clone is not supported');
    }

    public function subscribe(CommandHandler $handler): void
    {
        if (!isset($this->handlers[$handler::class])) {
            $this->handlers[$handler::class] = $handler;
        }
    }

    public function reset(): void
    {
        $this->handlers = [];
    }

    public function dispatch(Command $command, User $user): ?CommandResponse
    {
        if (0 === count($this->handlers)) {
            return null;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->isHandled($command)) {
                return $handler->handle($command, $user);
            }
        }

        return null;
    }
}
