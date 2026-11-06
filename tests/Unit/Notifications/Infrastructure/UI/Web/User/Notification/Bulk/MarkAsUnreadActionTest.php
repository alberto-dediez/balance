<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\UI\Web\User\Notification\Bulk;

use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadRequest;
use App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk\MarkAsUnreadAction;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class MarkAsUnreadActionTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCommandsAreEmptyWithoutElements(): void
    {
        $sut = new MarkAsUnreadAction();

        $this->assertSame([], $sut->commands($this->bulkRequest($sut, [])));
    }

    public function testBuildsOneCommandPerNotification(): void
    {
        $first = NotificationMother::from(recipientId: UserId::generate(), subject: 'Primera');
        $second = NotificationMother::from(recipientId: UserId::generate(), subject: 'Segunda');

        $sut = new MarkAsUnreadAction();

        $commands = $sut->commands($this->bulkRequest($sut, [$first, $second]));

        $this->assertCount(2, $commands);
        $this->assertInstanceOf(MarkNotificationAsUnreadRequest::class, $commands[0]);
        $this->assertInstanceOf(MarkNotificationAsUnreadRequest::class, $commands[1]);
        $this->assertSame((string) $first->id(), $commands[0]->notificationId);
        $this->assertSame((string) $second->id(), $commands[1]->notificationId);
    }

    public function testIgnoresElementsThatAreNotNotifications(): void
    {
        $notification = NotificationMother::randomInternal();

        $sut = new MarkAsUnreadAction();

        $commands = $sut->commands($this->bulkRequest($sut, [null, UserMother::adminUser(), $notification]));

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(MarkNotificationAsUnreadRequest::class, $commands[0]);
        $this->assertSame((string) $notification->id(), $commands[0]->notificationId);
    }

    public function testSuccessAndFailureMessagesUseTheSubject(): void
    {
        $notification = NotificationMother::from(recipientId: UserId::generate(), subject: 'Importación terminada');

        $sut = new MarkAsUnreadAction();

        [$command] = $sut->commands($this->bulkRequest($sut, [$notification]));

        $this->assertSame('Importación terminada', $sut->success($command)->getParameters()['{subject}']);
        $this->assertSame('Importación terminada', $sut->failure($command)->getParameters()['{subject}']);

        $unknown = new MarkNotificationAsUnreadRequest('11111111-1111-1111-1111-111111111111');

        $this->assertSame('11111111-1111-1111-1111-111111111111', $sut->success($unknown)->getParameters()['{subject}']);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $sut->failure($unknown)->getParameters()['{subject}']);
    }

    public function testIdAndName(): void
    {
        $sut = new MarkAsUnreadAction();

        $name = $sut->name();

        $this->assertSame('mark-as-unread', $sut->id());
        $this->assertInstanceOf(TranslatableMessage::class, $name);
        $this->assertSame('Marcar como no leída', $name->getMessage());
    }

    /** @param array<int, mixed> $elements */
    private function bulkRequest(MarkAsUnreadAction $action, array $elements): BulkRequest
    {
        $request = new BulkRequest();
        $request->elements = $elements;
        $request->action = $action;

        return $request;
    }
}
