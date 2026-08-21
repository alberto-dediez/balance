<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditThresholdReached;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Infrastructure\Notification\CreditThresholdReachedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditThresholdReachedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesEveryOwnerByInternalAndEmailChannels(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id()));
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id()));
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::Depleted));

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame(100, $notifications[0]->params['threshold']);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testDoesNotNotifyWhenTheAccountHasNoOwners(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id()));

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::Warning));

        $this->assertSame([], $notifier->notifications());
    }

    private function createSUT(
        SpySystemNotifier $notifier,
        Project $project,
        InMemoryAccountRepository $accountRepository,
        InMemoryUserRepository $userRepository,
    ): CreditThresholdReachedNotifier {
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        return new CreditThresholdReachedNotifier($notifier, $projectRepository, $accountRepository, $userRepository);
    }

    private function event(Account $account, Project $project, CreditAlertThreshold $threshold): CreditThresholdReached
    {
        return new CreditThresholdReached(
            creditPurchaseId: CreditPurchaseId::generate(),
            accountId: $account->id(),
            projectId: $project->id(),
            threshold: $threshold,
            purchasedTonnes: 10.0,
            consumedTonnes: 10.0,
            availableTonnes: 0.0,
            projectAvailableTonnes: 0.0,
            purchaseReference: 'ref-1',
        );
    }
}
