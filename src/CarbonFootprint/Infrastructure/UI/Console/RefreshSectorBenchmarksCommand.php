<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Console;

use App\CarbonFootprint\Application\SectorBenchmark\Refresh\RefreshSectorBenchmarksRequest;
use App\CarbonFootprint\Application\SectorBenchmark\Refresh\RefreshSectorBenchmarksService;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'carbon-footprint:sector-benchmarks:refresh',
    description: 'Recomputes the anonymous sector benchmarks of the carbon footprint intensity indicators',
)]
class RefreshSectorBenchmarksCommand extends Command
{
    public function __construct(
        private readonly RefreshSectorBenchmarksService $refreshSectorBenchmarks,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the refresh');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $response = $this->refreshSectorBenchmarks->__invoke(new RefreshSectorBenchmarksRequest(now: new \DateTimeImmutable('NOW')), $user);

        $io->success(sprintf('%d sector benchmarks published.', $response->benchmarks));

        return Command::SUCCESS;
    }
}
