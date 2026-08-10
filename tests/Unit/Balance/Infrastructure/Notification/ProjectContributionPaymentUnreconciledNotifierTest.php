<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Infrastructure\Notification\ProjectContributionPaymentUnreconciledNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProjectContributionPaymentUnreconciledNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesEveryAdministratorWhenAResolvedContributionIsPaid(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));
        $userRepository->add(UserMother::fromUsernameAndRole('owner', Role::Owner));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::expired();
        $contributions->add($contribution);

        $notifier = new SpySystemNotifier();

        new ProjectContributionPaymentUnreconciledNotifier($notifier, $contributions, $userRepository);

        $contribution->pay();

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame('expired', $notifications[0]->params['status']);
        $this->assertSame($contribution->totalAmount(), $notifications[0]->params['totalAmount']);
    }

    public function testAContributionPaidOnTimeNotifiesNobody(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        $notifier = new SpySystemNotifier();

        new ProjectContributionPaymentUnreconciledNotifier($notifier, $contributions, $userRepository);

        $contribution->pay();
        $contribution->pay();

        $this->assertCount(0, $notifier->notifications());
    }
}
