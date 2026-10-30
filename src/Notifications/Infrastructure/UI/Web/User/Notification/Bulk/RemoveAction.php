<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk;

use App\Notifications\Application\Notification\Remove\RemoveNotificationRequest;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction as RemoveActionTrait;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    use RemoveActionTrait;

    /** @var array<string, string> */
    private array $subjects = [];

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

            $commands[] = new RemoveNotificationRequest(
                notificationId: (string) $notification->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveNotificationRequest);

        return new TranslatableMessage('Se ha eliminado la notificación «{subject}»', ['{subject}' => $this->subjects[$command->notificationId] ?? $command->notificationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveNotificationRequest);

        return new TranslatableMessage('No se ha podido eliminar la notificación «{subject}»', ['{subject}' => $this->subjects[$command->notificationId] ?? $command->notificationId]);
    }
}
