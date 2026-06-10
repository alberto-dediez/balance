<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction as RemoveActionTrait;
use App\Waste\Application\AlertConfiguration\Remove\RemoveAlertConfigurationRequest;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
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

        foreach ($request->elements as $config) {
            if (!$config instanceof AlertConfiguration) {
                continue;
            }

            $commands[] = new RemoveAlertConfigurationRequest(
                alertConfigurationId: $config->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAlertConfigurationRequest);

        return new TranslatableMessage('Se ha eliminado la configuración de alerta {id}', ['{id}' => $command->alertConfigurationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAlertConfigurationRequest);

        return new TranslatableMessage('No se ha podido eliminar la configuración de alerta {id}', ['{id}' => $command->alertConfigurationId]);
    }
}
