<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\Alert;

use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        private readonly AccountRepository $accountRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Comprobación de alertas de residuos');

        $checkDate = new \DateTimeImmutable('YESTERDAY');

        $accountIds = array_map(
            static fn($account) => $account->id(),
            $this->accountRepository->findByFilter(new AccountFilter(limit: 0)),
        );

        $user = UserMother::systemUser();

        foreach ($accountIds as $accountId) {
            CommandBusDispatcher::instance()
                ->dispatch(
                    new DeferredCommand(
                        command: new CheckAlertsRequest(
                            checkDate: $checkDate,
                            accountId: $accountId,
                        ),
                    ),
                    $user,
                );
        }

        $io->success(sprintf('Se han encolado %d comprobaciones de cuenta', count($accountIds)));

        return Command::SUCCESS;
    }
}
