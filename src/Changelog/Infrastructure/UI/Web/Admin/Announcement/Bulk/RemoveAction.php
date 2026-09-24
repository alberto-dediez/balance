<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement\Bulk;

use App\Changelog\Application\Announcement\Remove\RemoveAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction as RemoveActionTrait;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    use RemoveActionTrait;

    public function commands(BulkRequest $request): array
    {
        if (0 == count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $announcement) {
            if (!$announcement instanceof Announcement) {
                continue;
            }

            $commands[] = new RemoveAnnouncementRequest(
                announcementId: $announcement->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAnnouncementRequest);

        return new TranslatableMessage('Se ha eliminado la novedad {id}', ['{id}' => (string) $command->announcementId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAnnouncementRequest);

        return new TranslatableMessage('No se ha podido eliminar la novedad {id}', ['{id}' => (string) $command->announcementId]);
    }
}
