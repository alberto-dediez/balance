<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Send;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Send\SendNotificationRequest;
use App\Notifications\Application\Notification\Send\SendNotificationService;
use App\Notifications\Domain\Model\Notification\AudienceType;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SendNotificationServiceTest extends TestCase
{
    private SpyDeferredCommandHandler $spy;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->spy = new SpyDeferredCommandHandler();
        CommandBusDispatcher::instance()->subscribe($this->spy);
    }

    public function testSendToAllPlatformFansOutPerRecipientAndChannel(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsername('a@example.com'));
        $userRepository->add(UserMother::fromUsername('b@example.com'));
        $userRepository->add(UserMother::fromUsername('c@example.com'));

        $sut = new SendNotificationService($userRepository, new NotificationAuthorizer());

        $response = $sut(new SendNotificationRequest(
            audienceType: AudienceType::AllPlatform,
            channels: [NotificationChannel::Internal, NotificationChannel::Email],
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::adminUser());

        $this->assertEquals(3, $response->recipientCount);
        $this->assertCount(6, $this->spy->commands());
    }

    public function testSendToAccountResolvesOnlyAccountUsers(): void
    {
        $accountId = AccountId::generate();
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('a@example.com', Role::User, $accountId));
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('b@example.com', Role::User, $accountId));
        $userRepository->add(UserMother::fromUsername('outsider@example.com'));

        $sut = new SendNotificationService($userRepository, new NotificationAuthorizer());

        $response = $sut(new SendNotificationRequest(
            audienceType: AudienceType::Account,
            targetAccountId: (string) $accountId,
            channels: [NotificationChannel::Internal],
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::adminUser());

        $this->assertEquals(2, $response->recipientCount);
        $this->assertCount(2, $this->spy->commands());
    }

    public function testSendToAccountRoleResolvesOnlyMatchingRole(): void
    {
        $accountId = AccountId::generate();
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('admin@example.com', Role::Admin, $accountId));
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $accountId));

        $sut = new SendNotificationService($userRepository, new NotificationAuthorizer());

        $response = $sut(new SendNotificationRequest(
            audienceType: AudienceType::AccountRole,
            targetAccountId: (string) $accountId,
            targetRole: Role::Admin,
            channels: [NotificationChannel::Internal],
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::adminUser());

        $this->assertEquals(1, $response->recipientCount);
        $this->assertCount(1, $this->spy->commands());
    }

    public function testSendToSingleUser(): void
    {
        $userRepository = new InMemoryUserRepository();
        $user = UserMother::from(UserId::generate(), 'target@example.com');
        $userRepository->add($user);

        $sut = new SendNotificationService($userRepository, new NotificationAuthorizer());

        $response = $sut(new SendNotificationRequest(
            audienceType: AudienceType::User,
            targetUserId: (string) $user->id(),
            channels: [NotificationChannel::Internal],
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::adminUser());

        $this->assertEquals(1, $response->recipientCount);
        $this->assertCount(1, $this->spy->commands());
    }

    public function testNonAdminCannotSend(): void
    {
        $sut = new SendNotificationService(new InMemoryUserRepository(), new NotificationAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new SendNotificationRequest(
            audienceType: AudienceType::AllPlatform,
            channels: [NotificationChannel::Internal],
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }
}
