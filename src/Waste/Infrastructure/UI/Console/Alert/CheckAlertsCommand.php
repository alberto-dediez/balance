<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\Alert;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
use App\Waste\Application\AlertCheckQueue\Register\RegisterAlertCheckQueueRequest;
use App\Waste\Application\AlertCheckQueue\Remove\RemoveAlertCheckQueueRequest;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationFilter;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'waste:alerts:check',
    description: 'Process the alert check queue and dispatch one check per pending account+date entry',
)]
class CheckAlertsCommand extends Command
{
    public function __construct(
        private readonly AlertCheckQueueRepository $alertCheckQueueRepository,
        private readonly AlertConfigurationRepository $alertConfigurationRepository,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('performer-email', InputArgument::REQUIRED, 'Email del usuario que ejecuta las comprobaciones');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Comprobación de alertas de residuos');

        $performerEmail = $input->getArgument('performer-email');
        $user = $this->userRepository->findByUsername($performerEmail);

        if (null === $user) {
            $io->error(sprintf('No se encontró ningún usuario con el email "%s"', $performerEmail));

            return Command::FAILURE;
        }

        $accountIds = $this->resolveAccountIds();
        $checkDate = new \DateTimeImmutable('YESTERDAY');

        if (count($accountIds) > 0) {
            foreach ($accountIds as $accountId) {
                CommandBusDispatcher::instance()
                    ->dispatch(
                        new RegisterAlertCheckQueueRequest($accountId, $checkDate),
                        $user,
                    );
            }
        }

        $pending = $this->alertCheckQueueRepository->findAll();

        if (0 === count($pending)) {
            $io->info('No hay entradas pendientes en la cola de comprobación de alertas');

            return Command::SUCCESS;
        }

        foreach ($pending as $entry) {
            assert(null !== $entry->accountId());

            CommandBusDispatcher::instance()
                ->dispatch(
                    new DeferredCommand(
                        command: new CheckAlertsRequest(
                            checkDate: $entry->checkDate(),
                            accountId: $entry->accountId(),
                        ),
                        priority: Priority::LOW,
                    ),
                    $user,
                );

            CommandBusDispatcher::instance()
                ->dispatch(
                    new RemoveAlertCheckQueueRequest($entry->id()),
                    $user,
                );
        }

        $io->success(sprintf('Se han encolado %d comprobaciones de alertas', count($pending)));

        return Command::SUCCESS;
    }

    /** @return array<AccountId> */
    private function resolveAccountIds(): array
    {
        $accountIds = [];

        $configurations = $this->alertConfigurationRepository->findByFilter(
            new AlertConfigurationFilter(
                limit: 0,
            ),
        );

        if (0 === count($configurations)) {
            return $accountIds;
        }

        foreach ($configurations as $configuration) {
            if ($configuration->hasActiveItems() && null !== $configuration->accountId()) {
                $accountIds[] = $configuration->accountId();
            }
        }

        return $accountIds;
    }
}
