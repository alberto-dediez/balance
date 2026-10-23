<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notifications;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationFilter;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class NotificationsContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->client->reset();
    }

    private function getNotificationRepository(): NotificationRepository
    {
        return $this->client->getContainer()->get(NotificationRepository::class);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    #[Given('notification with id :notificationId for user :username exists')]
    public function notificationWithIdForUserExists(string $notificationId, string $username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user, sprintf('User "%s" not found', $username));

        $notification = NotificationMother::fromIdAndRecipient($notificationId, $user->id());

        $this->getNotificationRepository()->add($notification);
    }

    #[Then('user :username has :count notifications with subject :subject')]
    public function userHasNotificationsWithSubject(string $username, int $count, string $subject): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user, sprintf('User "%s" not found', $username));

        $notifications = array_filter(
            $this->getNotificationRepository()->findByFilter(new NotificationFilter(recipientId: $user->id(), limit: 0)),
            static fn(Notification $notification): bool => $notification->subject() === $subject,
        );

        Assert::assertCount($count, $notifications);
    }
}
