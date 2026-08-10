<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\ProjectContribution;

use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsService;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'balance:expire-pending-contributions',
    description: 'Release the credit reserved by contributions whose payment window is over',
)]
class ExpireContributionsCommand extends Command
{
    public function __construct(private readonly ExpirePendingContributionsService $expirePendingContributions)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->expirePendingContributions->__invoke(
            new ExpirePendingContributionsRequest(new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );

        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf(
            '%d contribution(s) expired, %d confirmed from the gateway.',
            $response->expired,
            $response->confirmed,
        ));

        return Command::SUCCESS;
    }
}
