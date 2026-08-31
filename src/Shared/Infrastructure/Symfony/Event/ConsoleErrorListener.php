<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\ErrorReportFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ConsoleErrorListener
{
    public function __construct(
        private readonly ErrorReportFactory $errorReportFactory,
        private readonly ErrorReportNotifier $errorReportNotifier,
    ) {}

    public function __invoke(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();

        $report = $this->errorReportFactory->fromCommand(
            $event->getError(),
            $event->getExitCode(),
            $command instanceof Command ? $command->getName() : null,
        );

        ($this->errorReportNotifier)($report);
    }
}
