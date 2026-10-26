<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Application\Health\CheckHealthService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCheckCommand extends Command
{
    public function __construct(private readonly CheckHealthService $checkHealth)
    {
        parent::__construct();
    }

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
        $report = ($this->checkHealth)();

        $output->writeln(sprintf('status: %s', $report->isHealthy() ? 'ok' : 'error'));

        foreach ($report->results() as $result) {
            $output->writeln(sprintf('%s: %s', $result->name, $result->healthy ? 'ok' : 'error'));
        }

        return $report->isHealthy() ? Command::SUCCESS : Command::FAILURE;
    }
}
