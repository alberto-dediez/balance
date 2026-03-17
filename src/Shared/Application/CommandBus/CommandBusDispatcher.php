<?php

namespace App\Shared\Application\CommandBus;

use BadMethodCallException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;

class CommandBusDispatcher
{
    protected static ?CommandBusDispatcher $instance = null;

    /**
     * @var CommandHandler[] $handlers
     */
    protected array $handlers = [];

    private function __construct()
    {
    }

    public static function instance(): CommandBusDispatcher
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function __clone()
    {
        throw new BadMethodCallException("Clone is not supported");
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
        if (count($this->handlers) === 0) {
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
