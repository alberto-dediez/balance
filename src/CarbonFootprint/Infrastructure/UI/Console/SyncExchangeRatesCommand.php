<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Console;

use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesRequest;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesService;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'carbon-footprint:exchange-rates:sync',
    description: 'Downloads the daily ECB euro foreign exchange reference rates',
)]
class SyncExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly SyncExchangeRatesService $syncExchangeRates,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the synchronization');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $response = $this->syncExchangeRates->__invoke(new SyncExchangeRatesRequest(now: new \DateTimeImmutable('NOW')), $user);

        if ($response->failed) {
            $io->warning('Source unavailable: ' . $response->failure);

            return Command::SUCCESS;
        }

        $io->table(['Counter', 'Value'], [
            ['Date', $response->date?->format('Y-m-d')],
            ['Registered', $response->registered],
            ['Updated', $response->updated],
            ['Unchanged', $response->unchanged],
            ['Unknown currencies', implode(', ', $response->unknownCurrencies)],
        ]);

        return Command::SUCCESS;
    }
}
