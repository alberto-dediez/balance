<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\UI\Web\User\Notification\Bulk;

use App\Notifications\Application\Notification\Remove\RemoveNotificationRequest;
use App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk\RemoveAction;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveActionTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCommandsAreEmptyWithoutElements(): void
    {
        $sut = new RemoveAction();

        $this->assertSame([], $sut->commands($this->bulkRequest($sut, [])));
    }

    public function testBuildsOneCommandPerNotification(): void
    {
        $first = NotificationMother::from(recipientId: UserId::generate(), subject: 'Primera');
        $second = NotificationMother::from(recipientId: UserId::generate(), subject: 'Segunda');

        $sut = new RemoveAction();

        $commands = $sut->commands($this->bulkRequest($sut, [$first, $second]));

        $this->assertCount(2, $commands);
        $this->assertInstanceOf(RemoveNotificationRequest::class, $commands[0]);
        $this->assertInstanceOf(RemoveNotificationRequest::class, $commands[1]);
        $this->assertSame((string) $first->id(), $commands[0]->notificationId);
        $this->assertSame((string) $second->id(), $commands[1]->notificationId);
    }

    public function testIgnoresElementsThatAreNotNotifications(): void
    {
        $notification = NotificationMother::randomInternal();

        $sut = new RemoveAction();

        $commands = $sut->commands($this->bulkRequest($sut, [null, UserMother::adminUser(), $notification]));

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(RemoveNotificationRequest::class, $commands[0]);
        $this->assertSame((string) $notification->id(), $commands[0]->notificationId);
    }

    public function testSuccessAndFailureMessagesUseTheSubject(): void
    {
        $notification = NotificationMother::from(recipientId: UserId::generate(), subject: 'Importación terminada');

        $sut = new RemoveAction();

        [$command] = $sut->commands($this->bulkRequest($sut, [$notification]));

        $this->assertSame('Importación terminada', $sut->success($command)->getParameters()['{subject}']);
        $this->assertSame('Importación terminada', $sut->failure($command)->getParameters()['{subject}']);

        $unknown = new RemoveNotificationRequest('11111111-1111-1111-1111-111111111111');

        $this->assertSame('11111111-1111-1111-1111-111111111111', $sut->success($unknown)->getParameters()['{subject}']);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $sut->failure($unknown)->getParameters()['{subject}']);
    }

    public function testIdAndName(): void
    {
        $sut = new RemoveAction();

        $name = $sut->name();

        $this->assertSame('remove', $sut->id());
        $this->assertInstanceOf(TranslatableMessage::class, $name);
        $this->assertSame('Eliminar', $name->getMessage());
    }

    /** @param array<int, mixed> $elements */
    private function bulkRequest(RemoveAction $action, array $elements): BulkRequest
    {
        $request = new BulkRequest();
        $request->elements = $elements;
        $request->action = $action;

        return $request;
    }
}
