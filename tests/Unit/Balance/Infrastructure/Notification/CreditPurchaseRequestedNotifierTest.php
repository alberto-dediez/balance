<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Infrastructure\Notification\CreditPurchaseRequestedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseRequestedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesEveryAdministratorWithProjectInfo(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));
        $userRepository->add(UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin));
        $userRepository->add(UserMother::fromUsernameAndRole('owner', Role::Owner));

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, $projectRepository, $userRepository);

        CreditPurchaseMother::pending(projectId: $project->id());

        $notifications = $notifier->notifications();

        $this->assertCount(4, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }
}
