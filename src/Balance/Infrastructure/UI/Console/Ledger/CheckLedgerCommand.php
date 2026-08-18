<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\Ledger;

use App\Balance\Application\LedgerEntry\Check\CheckLedgerRequest;
use App\Balance\Application\LedgerEntry\Check\CheckLedgerService;
use App\Balance\Application\LedgerEntry\Check\LedgerFinding;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'balance:ledger:check',
    description: 'Verify that every paid contribution is backed by a balanced set of ledger entries',
)]
class CheckLedgerCommand extends Command
{
    public function __construct(
        private readonly CheckLedgerService $checkLedger,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the check')
            ->addOption('repair', null, InputOption::VALUE_NONE, 'Record the missing ledger entries')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Only check contributions and payouts from this date')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Only check contributions and payouts up to this date')
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

        $response = $this->checkLedger->__invoke(
            new CheckLedgerRequest(
                repair: (bool) $input->getOption('repair'),
                from: $this->date($input, 'from'),
                to: $this->date($input, 'to'),
            ),
            $user,
        );

        if (!$response->hasFindings()) {
            $io->success(sprintf('%d contribution(s) checked, ledger is consistent.', $response->checkedContributions));

            return Command::SUCCESS;
        }

        $io->table(
            ['Check', 'Subject', 'Detail', 'Repaired'],
            array_map(
                static fn(LedgerFinding $finding): array => [
                    $finding->code->value,
                    $finding->subjectId,
                    $finding->detail,
                    $finding->repaired ? 'yes' : 'no',
                ],
                $response->findings,
            ),
        );

        $io->warning(sprintf(
            '%d finding(s) over %d contribution(s), %d repaired.',
            count($response->findings),
            $response->checkedContributions,
            $response->repairedCount(),
        ));

        return Command::FAILURE;
    }

    private function date(InputInterface $input, string $option): ?\DateTimeImmutable
    {
        $value = $input->getOption($option);

        if (!is_string($value) || '' === $value) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }
}
