<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use Psr\Log\LoggerInterface;

class ToolRunner
{
    public const string UNEXPECTED_ERROR = 'Error inesperado';

    public function __construct(
        private readonly Toolbox $toolbox,
        private readonly LoggerInterface $logger,
    ) {}

    /** @param array<string, mixed> $input */
    public function run(string $name, array $input, ToolMode $allowed, User $user, ToolContext $context): ToolOutcome
    {
        $tool = $this->toolbox->get($name);

        if (null === $tool || $allowed !== $tool->mode() || !$tool->isAvailableTo($user)) {
            throw new ToolNotAllowedException($name);
        }

        $start = hrtime(true);
        $arguments = null;

        try {
            $arguments = ToolArgumentsFactory::create($tool->parameters(), $input, $context);
            $result = $tool->execute($arguments, $user, $context);

            return ToolOutcome::succeeded($arguments, $result, $this->elapsed($start));
        } catch (InvalidToolArgumentsException $exception) {
            return ToolOutcome::failed(implode(' ', $exception->errors()), $arguments, $this->elapsed($start));
        } catch (UnauthorizedActionException $exception) {
            return ToolOutcome::failed('No tienes permiso para hacer esto: ' . $exception->getMessage(), $arguments, $this->elapsed($start));
        } catch (\InvalidArgumentException|\DomainException $exception) {
            return ToolOutcome::failed($exception->getMessage(), $arguments, $this->elapsed($start));
        } catch (\Throwable $exception) {
            $this->logger->error('The assistant tool failed unexpectedly', ['tool' => $name, 'exception' => $exception]);

            return ToolOutcome::failed(self::UNEXPECTED_ERROR, $arguments, $this->elapsed($start));
        }
    }

    /** @param array<string, mixed> $input */
    public function argumentsOf(string $name, array $input, ToolContext $context): ToolArguments
    {
        $tool = $this->toolbox->get($name);

        if (null === $tool) {
            throw new ToolNotAllowedException($name);
        }

        return ToolArgumentsFactory::create($tool->parameters(), $input, $context);
    }

    private function elapsed(int|float $start): int
    {
        return (int) round((hrtime(true) - $start) / 1_000_000);
    }
}
