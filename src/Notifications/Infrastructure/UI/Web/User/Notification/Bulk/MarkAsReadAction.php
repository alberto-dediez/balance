<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk;

use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class MarkAsReadAction implements BulkAction
{
    /** @var array<string, string> */
    private array $subjects = [];

    public function id(): string
    {
        return 'mark-as-read';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Marcar como leída');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $notification) {
            if (!$notification instanceof Notification) {
                continue;
            }

            $this->subjects[(string) $notification->id()] = $notification->subject();

            $commands[] = new MarkNotificationAsReadRequest(
                notificationId: (string) $notification->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof MarkNotificationAsReadRequest);

        return new TranslatableMessage('Se ha marcado como leída la notificación «{subject}»', ['{subject}' => $this->subjects[$command->notificationId] ?? $command->notificationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof MarkNotificationAsReadRequest);

        return new TranslatableMessage('No se ha podido marcar como leída la notificación «{subject}»', ['{subject}' => $this->subjects[$command->notificationId] ?? $command->notificationId]);
    }
}
