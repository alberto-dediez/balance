<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditThresholdReached;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Infrastructure\Notification\CreditThresholdReachedNotifier;
use App\Notifications\Application\Notification\Notify\SystemNotification;
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

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::Depleted));

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame('@balance/notification/credit-threshold-reached.notification.html.twig', $notifications[0]->template);
        $this->assertSame('@balance/notification/credit-threshold-reached.email.html.twig', $notifications[1]->template);
        $this->assertSame(100, $notifications[0]->params['threshold']);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testNotifiesEveryAdministratorByInternalAndEmailChannels(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id());
        $firstAdmin = UserMother::fromUsernameAndRole('admin1', Role::Admin);
        $secondAdmin = UserMother::fromUsernameAndRole('admin2', Role::Admin);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($owner);
        $userRepository->add($user);
        $userRepository->add($firstAdmin);
        $userRepository->add($secondAdmin);

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $event = $this->event($account, $project, CreditAlertThreshold::Depleted);
        $sut->handle($event);

        $notifications = $notifier->notifications();

        $this->assertCount(6, $notifications);

        $adminNotifications = array_values(array_filter(
            $notifications,
            static fn(SystemNotification $notification): bool => in_array(
                (string) $notification->recipientId,
                [(string) $firstAdmin->id(), (string) $secondAdmin->id()],
                true,
            ),
        ));

        $this->assertCount(4, $adminNotifications);

        foreach ($adminNotifications as $notification) {
            $this->assertSame($account->name(), $notification->params['accountName']);
            $this->assertSame((string) $event->creditPurchaseId(), $notification->params['creditPurchaseId']);
            $this->assertSame(100, $notification->params['threshold']);
            $this->assertSame(['{account}' => $account->name(), '{project}' => $project->name()], $notification->subjectParams);
        }

        foreach ([$firstAdmin, $secondAdmin] as $admin) {
            $this->assertSame(
                [
                    NotificationChannel::Internal->name => '@balance/notification/credit-threshold-reached-admin.notification.html.twig',
                    NotificationChannel::Email->name => '@balance/notification/credit-threshold-reached-admin.email.html.twig',
                ],
                $this->templatesByChannel($notifications, (string) $admin->id()),
            );
        }

        $this->assertSame(
            [
                NotificationChannel::Internal->name => '@balance/notification/credit-threshold-reached.notification.html.twig',
                NotificationChannel::Email->name => '@balance/notification/credit-threshold-reached.email.html.twig',
            ],
            $this->templatesByChannel($notifications, (string) $owner->id()),
        );

        foreach ($notifications as $notification) {
            $this->assertNotSame((string) $user->id(), (string) $notification->recipientId);
        }
    }

    /**
     * @dataProvider administratorSubjects
     */
    public function testAdministratorSubjectDependsOnTheThreshold(CreditAlertThreshold $threshold, string $subject): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, $threshold));

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame($subject, $notifications[0]->subject);
        $this->assertSame($subject, $notifications[1]->subject);
    }

    /** @return iterable<string, array{CreditAlertThreshold, string}> */
    public static function administratorSubjects(): iterable
    {
        yield 'warning' => [CreditAlertThreshold::Warning, 'A la cuenta {account} le queda poco crédito en el proyecto {project}'];
        yield 'high' => [CreditAlertThreshold::High, 'El crédito de la cuenta {account} en el proyecto {project} está casi agotado'];
        yield 'depleted' => [CreditAlertThreshold::Depleted, 'La cuenta {account} se ha quedado sin crédito en el proyecto {project}'];
    }

    public function testNotifiesAdministratorsWhenTheAccountHasNoOwners(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $admin = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id()));
        $userRepository->add($admin);

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::Warning));

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame((string) $admin->id(), (string) $notifications[0]->recipientId);
        $this->assertSame((string) $admin->id(), (string) $notifications[1]->recipientId);
    }

    public function testDoesNotNotifyAnyoneWithoutOwnersNorAdministrators(): void
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

    public function testDoesNotNotifySuperAdministrators(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $both = UserMother::fromUsernameAndRole('both', Role::Admin);
        $both->grantRole(Role::SuperAdmin);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('super', Role::SuperAdmin));
        $userRepository->add($both);

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::Depleted));

        $this->assertSame([], $notifier->notifications());
    }

    public function testOwnerWhoIsAlsoAdministratorOnlyReceivesTheOwnerNotification(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add($account);

        $ownerAdmin = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $ownerAdmin->grantRole(Role::Admin);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($ownerAdmin);

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, $accountRepository, $userRepository);
        $sut->handle($this->event($account, $project, CreditAlertThreshold::High));

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame('@balance/notification/credit-threshold-reached.notification.html.twig', $notifications[0]->template);
        $this->assertSame('@balance/notification/credit-threshold-reached.email.html.twig', $notifications[1]->template);
    }

    public function testUsesTheAccountIdAsNameWhenTheAccountDoesNotExist(): void
    {
        $account = AccountMother::random();
        $project = ProjectMother::random();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $notifier = new SpySystemNotifier();

        $sut = $this->createSUT($notifier, $project, new InMemoryAccountRepository(), $userRepository);
        $event = $this->event($account, $project, CreditAlertThreshold::Warning);
        $sut->handle($event);

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame((string) $event->accountId(), $notifications[0]->params['accountName']);
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

    /**
     * @param SystemNotification[] $notifications
     *
     * @return array<string, string|null>
     */
    private function templatesByChannel(array $notifications, string $recipientId): array
    {
        $templates = [];

        foreach ($notifications as $notification) {
            if ((string) $notification->recipientId !== $recipientId) {
                continue;
            }

            $this->assertArrayNotHasKey($notification->channel->name, $templates);
            $templates[$notification->channel->name] = $notification->template;
        }

        return $templates;
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
