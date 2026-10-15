<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile\Bulk;

use App\CarbonFootprint\Application\UseProfile\Remove\RemoveUseProfileRequest;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
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

        foreach ($request->elements as $useProfile) {
            if (!$useProfile instanceof UseProfile) {
                continue;
            }

            $commands[] = new RemoveUseProfileRequest(
                useProfileId: $useProfile->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveUseProfileRequest);

        return new TranslatableMessage('Se ha eliminado el perfil de uso {id}', ['{id}' => $command->useProfileId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveUseProfileRequest);

        return new TranslatableMessage('No se ha podido eliminar el perfil de uso {id}', ['{id}' => $command->useProfileId]);
    }
}
