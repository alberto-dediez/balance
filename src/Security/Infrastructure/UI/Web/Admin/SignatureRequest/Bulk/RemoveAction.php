<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest\Bulk;

use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
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

        foreach ($request->elements as $signatureRequest) {
            if (!$signatureRequest instanceof SignatureRequest) {
                continue;
            }

            $commands[] = new RemoveSignatureRequestRequest(
                signatureRequestId: $signatureRequest->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSignatureRequestRequest);

        return new TranslatableMessage('Se ha eliminado la solicitud de firma {id}', ['{id}' => $command->signatureRequestId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSignatureRequestRequest);

        return new TranslatableMessage('No se ha podido eliminar la solicitud de firma {id}', ['{id}' => $command->signatureRequestId]);
    }
}
