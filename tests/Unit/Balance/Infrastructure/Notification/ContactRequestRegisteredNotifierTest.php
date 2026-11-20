<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestManaged;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRegistered;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Infrastructure\Notification\ContactRequestRegisteredNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ContactRequestRegisteredNotifierTest extends TestCase
{
    private Account $account;
    private InMemoryAccountRepository $accountRepository;
    private InMemoryUserRepository $userRepository;
    private SpySystemNotifier $notifier;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->account = AccountMother::random();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->accountRepository->add($this->account);
        $this->userRepository = new InMemoryUserRepository();
        $this->notifier = new SpySystemNotifier();
    }

    public function testIsSubscribedToContactRequestRegistered(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo($this->event()));
        $this->assertFalse($this->createSUT()->isSubscribedTo(
            new ContactRequestManaged(ContactRequestId::generate(), UserId::generate(), new \DateTimeImmutable('NOW')),
        ));
    }

    public function testNotifiesEveryAdministratorByInternalAndEmailChannels(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRole('admin1', Role::Admin));
        $this->userRepository->add(UserMother::fromUsernameAndRole('admin2', Role::Admin));

        $event = $this->event();
        $this->createSUT()->handle($event);

        $notifications = $this->notifier->notifications();

        $this->assertCount(4, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame('@balance/notification/contact-request-registered.notification.html.twig', $notifications[0]->template);
        $this->assertSame('@balance/notification/contact-request-registered.email.html.twig', $notifications[1]->template);
        $this->assertSame('Nueva solicitud de contacto para compensar de la cuenta {account}', $notifications[0]->subject);
        $this->assertSame(['{account}' => $this->account->name()], $notifications[0]->subjectParams);
        $this->assertSame((string) $event->contactRequestId(), $notifications[0]->params['contactRequestId']);
        $this->assertSame($this->account->name(), $notifications[0]->params['accountName']);
        $this->assertSame('ana@example.com', $notifications[0]->params['contactEmail']);
    }

    public function testDoesNotNotifySuperAdministrators(): void
    {
        $both = UserMother::fromUsernameAndRole('both', Role::Admin);
        $both->grantRole(Role::SuperAdmin);

        $this->userRepository->add(UserMother::fromUsernameAndRole('super', Role::SuperAdmin));
        $this->userRepository->add($both);

        $this->createSUT()->handle($this->event());

        $this->assertSame([], $this->notifier->notifications());
    }

    public function testDoesNotNotifyTheAccountOwners(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id()));

        $this->createSUT()->handle($this->event());

        $this->assertSame([], $this->notifier->notifications());
    }

    public function testDoesNothingWithoutAdministrators(): void
    {
        $this->createSUT()->handle($this->event());

        $this->assertSame([], $this->notifier->notifications());
    }

    private function event(): ContactRequestRegistered
    {
        return new ContactRequestRegistered(
            contactRequestId: ContactRequestId::generate(),
            accountId: $this->account->id(),
            propertyId: null,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: 'response-1',
            originName: 'Congreso Anual',
            co2eqKg: 150.0,
            contactName: 'Ana García',
            contactEmail: 'ana@example.com',
            contactPhone: null,
            contactCountry: 'ESP',
            contactIsCompany: false,
            contactCompanyName: null,
            contactVatNumber: null,
            comment: null,
            locale: Locale::Spanish,
        );
    }

    private function createSUT(): ContactRequestRegisteredNotifier
    {
        return new ContactRequestRegisteredNotifier($this->notifier, $this->accountRepository, $this->userRepository);
    }
}
