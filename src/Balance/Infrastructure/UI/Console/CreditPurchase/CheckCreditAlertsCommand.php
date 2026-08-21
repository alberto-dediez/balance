<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\CreditPurchase;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsService;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:balance:check-credit-alerts',
    description: 'Notify the account owners whose purchased credit is running low on a compensation project',
)]
class CheckCreditAlertsCommand extends Command
{
    public function __construct(
        private readonly CheckCreditAlertsService $checkCreditAlerts,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the check')
            ->addOption('account', null, InputOption::VALUE_REQUIRED, 'Only check this account')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Only check this project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $accountId = $this->option($input, 'account');
        $projectId = $this->option($input, 'project');

        $response = $this->checkCreditAlerts->__invoke(
            new CheckCreditAlertsRequest(
                accountId: null !== $accountId ? new AccountId($accountId) : null,
                projectId: null !== $projectId ? new ProjectId($projectId) : null,
                now: new \DateTimeImmutable('NOW'),
            ),
            $user,
        );

        $io->success(sprintf(
            '%d purchase(s) evaluated, %d alert(s) sent.',
            $response->evaluated,
            $response->alerted,
        ));

        return Command::SUCCESS;
    }

    private function option(InputInterface $input, string $option): ?string
    {
        $value = $input->getOption($option);

        if (!is_string($value) || '' === $value) {
            return null;
        }

        return $value;
    }
}
