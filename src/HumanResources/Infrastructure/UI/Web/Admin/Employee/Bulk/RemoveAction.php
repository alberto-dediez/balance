<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Admin\Employee\Bulk;

use App\HumanResources\Application\Employee\Remove\RemoveEmployeeRequest;
use App\HumanResources\Domain\Model\Employee\Employee;
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

        foreach ($request->elements as $employee) {
            if (!$employee instanceof Employee) {
                continue;
            }

            $commands[] = new RemoveEmployeeRequest(
                employeeId: $employee->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEmployeeRequest);

        return new TranslatableMessage('Employee {id} has been deleted', ['{id}' => $command->employeeId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEmployeeRequest);

        return new TranslatableMessage('Could not delete employee {id}', ['{id}' => $command->employeeId]);
    }
}
