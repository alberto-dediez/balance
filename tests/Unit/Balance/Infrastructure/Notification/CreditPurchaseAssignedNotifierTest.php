<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseAssigned;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseCancelled;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Infrastructure\Notification\CreditPurchaseAssignedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseAssignedNotifierTest extends TestCase
{
    private Account $account;
    private Project $project;
    private InMemoryAccountRepository $accountRepository;
    private InMemoryUserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private CreditPurchaseAssignedNotifier $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->account = AccountMother::random();
        $this->project = ProjectMother::random();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->accountRepository->add($this->account);
        $this->userRepository = new InMemoryUserRepository();
        $this->spyNotifier = new SpySystemNotifier();

        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($this->project);

        $this->sut = new CreditPurchaseAssignedNotifier(
            $this->spyNotifier,
            $projectRepository,
            $this->accountRepository,
            $this->userRepository,
        );
    }

    public function testIsSubscribedToCreditPurchaseAssigned(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event()));
        $this->assertFalse($this->sut->isSubscribedTo(new CreditPurchaseCancelled(CreditPurchaseId::generate())));
    }

    public function testNotifiesEachOwnerOfTheAccountOnlyByTheInternalChannel(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id());
        $secondOwner = UserMother::fromUsernameAndRoleAndAccount('owner2', Role::Owner, $this->account->id());
        $this->userRepository->add($owner);
        $this->userRepository->add($secondOwner);
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $this->account->id()));
        $this->userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->sut->handle($this->event());

        $notifications = $this->spyNotifier->notifications();

        $this->assertCount(2, $notifications);

        foreach ($notifications as $notification) {
            $this->assertSame(NotificationChannel::Internal, $notification->channel);
            $this->assertSame('Se han asignado créditos de CO2 a su cuenta', $notification->subject);
            $this->assertSame('@balance/notification/credit-purchase-assigned.notification.html.twig', $notification->template);
            $this->assertSame(25.0, $notification->params['purchasedTonnes']);
            $this->assertSame('ASSIGN-1', $notification->params['reference']);
            $this->assertSame($this->project->name(), $notification->params['projectName']);
        }

        $recipients = array_map(static fn($notification): string => (string) $notification->recipientId, $notifications);

        $this->assertSame(
            array_map(static fn(User $user): string => (string) $user->id(), [$owner, $secondOwner]),
            $recipients,
        );
    }

    public function testDoesNotNotifyWhenTheAccountHasNoOwners(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $this->account->id()));
        $this->userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->sut->handle($this->event());

        $this->assertSame([], $this->spyNotifier->notifications());
    }

    private function event(): CreditPurchaseAssigned
    {
        return new CreditPurchaseAssigned(
            creditPurchaseId: CreditPurchaseId::generate(),
            accountId: $this->account->id(),
            projectId: $this->project->id(),
            purchasedTonnes: 25.0,
            amount: null,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'ASSIGN-1',
            certificate: null,
            assignedBy: UserId::generate(),
        );
    }
}
