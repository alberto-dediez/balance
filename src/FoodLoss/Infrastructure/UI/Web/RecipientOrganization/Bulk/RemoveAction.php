<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization\Bulk;

use App\FoodLoss\Application\RecipientOrganization\Remove\RemoveRecipientOrganizationRequest;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
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
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $recipientOrganization) {
            if (!$recipientOrganization instanceof RecipientOrganization) {
                continue;
            }

            $commands[] = new RemoveRecipientOrganizationRequest(
                recipientOrganizationId: $recipientOrganization->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRecipientOrganizationRequest);

        return new TranslatableMessage('Se ha eliminado la entidad receptora {id}', ['{id}' => (string) $command->recipientOrganizationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRecipientOrganizationRequest);

        return new TranslatableMessage('No se ha podido eliminar la entidad receptora {id}', ['{id}' => (string) $command->recipientOrganizationId]);
    }
}
