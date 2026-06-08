<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\Alert;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
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
    description: 'Enqueue an alert check per account',
)]
class CheckAlertsCommand extends Command
{
    public function __construct(
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

        $checkDate = new \DateTimeImmutable('YESTERDAY');

        $accountIds = $this->resolveAccountIds();

        foreach ($accountIds as $accountId) {
            CommandBusDispatcher::instance()
                ->dispatch(
                    new DeferredCommand(
                        command: new CheckAlertsRequest(
                            checkDate: $checkDate,
                            accountId: $accountId,
                        ),
                        priority: Priority::LOW
                    ),
                    $user,
                );
        }

        $io->success(sprintf('Se han encolado %d comprobaciones de cuenta', count($accountIds)));

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
