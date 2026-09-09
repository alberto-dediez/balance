<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account\Bulk;

use App\Security\Application\Account\AssignConsultant\AssignConsultantRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class AssignConsultant implements BulkAction
{
    public function id(): string
    {
        return 'assign-consultant';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Asignar consultora');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        if (!$request instanceof BulkAccountRequest) {
            throw new \RuntimeException('Invalid BulkRequest sent!');
        }

        $commands = [];

        foreach ($request->elements as $account) {
            if (null === $account) {
                continue;
            }
            if (null === $account->id()) {
                continue;
            }
            $commands[] = new AssignConsultantRequest(
                accountId: $account->id(),
                consultantAccountId: $request->consultantAccountId,
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof AssignConsultantRequest);

        return new TranslatableMessage('Se ha asignado la consultora seleccionada a la cuenta {id}', ['{id}' => $command->accountId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof AssignConsultantRequest);

        return new TranslatableMessage('No se ha podido asignar la consultora seleccionada a la cuenta {id}', ['{id}' => $command->accountId]);
    }
}
