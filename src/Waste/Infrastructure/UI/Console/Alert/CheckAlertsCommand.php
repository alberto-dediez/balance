<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\Alert;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
use App\Waste\Application\Alert\Check\CheckAlertsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'waste:alerts:check',
    description: 'Check alerts for all accounts',
)]
class CheckAlertsCommand extends Command
{
    public function __construct(
        private readonly CheckAlertsService $checkAlertsService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('account-id', null, InputOption::VALUE_OPTIONAL, 'Run only for a specific account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Comprobación de alertas de residuos');

        $accountIdOption = $input->getOption('account-id');
        $accountId = is_string($accountIdOption) && '' !== $accountIdOption
            ? new AccountId($accountIdOption)
            : null;

        $user = UserMother::fromUsernameAndRole('system', Role::Admin);

        $response = $this->checkAlertsService->__invoke(
            new CheckAlertsRequest(
                checkDate: new \DateTimeImmutable('NOW'),
                accountId: $accountId,
            ),
            $user,
        );

        $io->success(sprintf('Se han detectado %d alertas', $response->detectedAlerts));

        return Command::SUCCESS;
    }
}
