<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Company\Bulk;

use App\Property\Application\Company\Remove\RemoveCompanyRequest;
use App\Property\Domain\Model\Company\Company;
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

        foreach ($request->elements as $company) {
            if (!$company instanceof Company) {
                continue;
            }

            $commands[] = new RemoveCompanyRequest(
                companyId: $company->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCompanyRequest);

        return new TranslatableMessage('Se ha eliminado la empresa {id}', ['{id}' => $command->companyId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCompanyRequest);

        return new TranslatableMessage('No se ha podido eliminar la empresa {id}', ['{id}' => $command->companyId]);
    }
}
