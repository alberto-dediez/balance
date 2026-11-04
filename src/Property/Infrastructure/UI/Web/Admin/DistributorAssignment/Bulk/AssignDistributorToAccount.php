<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\DistributorAssignment\Bulk;

use App\Property\Application\DistributorAssignment\Register\RegisterDistributorAssignmentRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\BulkAccountRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class AssignDistributorToAccount implements BulkAction
{
    public function id(): string
    {
        return 'assign-distributor';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Asignar distribuidor');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        if (!$request instanceof BulkAccountRequest) {
            throw new \RuntimeException('Invalid BulkRequest sent!');
        }

        if (!$request->distributorAccountId instanceof AccountId) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $account) {
            if (null === $account) {
                continue;
            }

            $commands[] = new RegisterDistributorAssignmentRequest(
                distributorAccountId: $request->distributorAccountId,
                accountId: $account->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RegisterDistributorAssignmentRequest);

        return new TranslatableMessage('Se ha asignado el distribuidor seleccionado a la cuenta {id}', ['{id}' => $command->accountId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RegisterDistributorAssignmentRequest);

        return new TranslatableMessage('No se ha podido asignar el distribuidor seleccionado a la cuenta {id}', ['{id}' => $command->accountId]);
    }
}
