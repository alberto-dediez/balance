<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\CreditPurchase;

use App\Balance\Application\CreditRange\Check\CheckCreditRangesRequest;
use App\Balance\Application\CreditRange\Check\CheckCreditRangesService;
use App\Balance\Application\CreditRange\Check\CreditRangeFinding;
use App\Balance\Application\CreditRange\Check\CreditRangeFindingCode;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'balance:credit-ranges:check',
    description: 'Verify that every received credit lot covers exactly the transferred range of its serial',
)]
class CheckCreditRangesCommand extends Command
{
    public function __construct(
        private readonly CheckCreditRangesService $checkCreditRanges,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the check')
            ->addOption('repair', null, InputOption::VALUE_NONE, 'Correct the range of the affected lots')
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

        $repair = (bool) $input->getOption('repair');

        $response = $this->checkCreditRanges->__invoke(new CheckCreditRangesRequest(repair: $repair), $user);

        if (!$response->hasFindings()) {
            $io->success(sprintf('%d received lot(s) checked, every range is correct.', $response->checkedLots));

            return Command::SUCCESS;
        }

        $io->table(
            ['Check', 'Lot', 'Detail', 'Repaired'],
            array_map(
                static fn(CreditRangeFinding $finding): array => [
                    $finding->code->value,
                    $finding->subjectId,
                    $finding->detail,
                    $finding->repaired ? 'yes' : 'no',
                ],
                $response->findings,
            ),
        );

        $pendingMismatches = array_filter(
            $response->findings,
            static fn(CreditRangeFinding $finding): bool => CreditRangeFindingCode::RangeMismatch === $finding->code && !$finding->repaired,
        );

        $overcededRanges = array_filter(
            $response->findings,
            static fn(CreditRangeFinding $finding): bool => CreditRangeFindingCode::OvercededRange === $finding->code && !$finding->repaired,
        );

        $blockingFindings = array_filter(
            $response->findings,
            static fn(CreditRangeFinding $finding): bool => CreditRangeFindingCode::OvercededRange !== $finding->code || !$finding->repaired,
        );

        $io->warning(sprintf(
            '%d finding(s) over %d received lot(s), %d range mismatch(es) pending, %d overceded range(s).',
            count($response->findings),
            $response->checkedLots,
            count($pendingMismatches),
            count($overcededRanges),
        ));

        if (!$repair) {
            return [] === $blockingFindings ? Command::SUCCESS : Command::FAILURE;
        }

        return [] === $pendingMismatches && [] === $overcededRanges ? Command::SUCCESS : Command::FAILURE;
    }
}
