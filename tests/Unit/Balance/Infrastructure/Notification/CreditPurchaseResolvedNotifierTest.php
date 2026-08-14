<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Infrastructure\Notification\CreditPurchaseResolvedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseResolvedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesRequesterOnFullValidationWithProjectInfo(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate(10.0, CreditPurchasePaymentType::PaidByClient, 'REF-1', null, UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications[0]->recipientId->equals($requester->id()));
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testNotifiesRequesterOnPartialValidationWithProjectInfo(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate(6.0, CreditPurchasePaymentType::PaidByClient, 'REF-2', null, UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('parcial', $notifications[0]->subject);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testNotifiesRequesterOnRejection(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->reject('Sin disponibilidad', UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications[0]->recipientId->equals($requester->id()));
    }
}
