<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\ProjectContribution;

use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsService;
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
    name: 'app:balance:reallocate-contributions',
    description: 'Allocate available credit to the paid contributions that are still underallocated',
)]
class ReallocateContributionsCommand extends Command
{
    public function __construct(
        private readonly ReallocateAllUnderallocatedContributionsService $reallocateAllUnderallocatedContributions,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the reallocation')
            ->addOption('account', null, InputOption::VALUE_REQUIRED, 'Only reallocate this account')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Only reallocate this project')
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

        $response = $this->reallocateAllUnderallocatedContributions->__invoke(
            new ReallocateAllUnderallocatedContributionsRequest(
                accountId: null !== $accountId ? new AccountId($accountId) : null,
                projectId: null !== $projectId ? new ProjectId($projectId) : null,
            ),
            $user,
        );

        $io->success(sprintf(
            '%d account/project pair(s) processed, %d contribution(s) covered, %s t reallocated.',
            $response->pairsProcessed,
            $response->contributionsCovered,
            (string) $response->reallocatedTonnes,
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
