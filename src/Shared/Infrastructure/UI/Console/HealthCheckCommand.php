<?php

namespace App\Shared\Infrastructure\UI\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('health:check')
            ->setDescription('Check system health.')
            ->setHelp('This command allows you to check if the system is working')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("status: ok");

        return Command::SUCCESS;
    }
}