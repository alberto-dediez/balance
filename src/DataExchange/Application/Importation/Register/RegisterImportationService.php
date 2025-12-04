<?php

namespace App\DataExchange\Application\Importation\Register;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterImportationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private ImportationRepository $importationRepository, private ImportationAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterImportationRequest $request, User $user): RegisterImportationResponse
    {
        $id = ImportationId::generate();

        $file = new ImportationFile(
            path: $request->path,
            extension: $request->extension,
            offset: $request->offset
        );

        $importation = new Importation(
            id: $id,
            accountId: $request->accountId,
            file: $file
        );

        $this->authorizer->denyUnlessCanDoThat($importation, Action::Register, $user);

        $importation->register(
            name: $request->name,
            type: $request->type,
            accountId: $request->accountId,
            registeredBy: $user->id()
        );

        $this->importationRepository->add($importation);

        return new RegisterImportationResponse(
            importationId: $importation->id(),
            name: $importation->name(),
            type: $importation->type(),
            accountId: $importation->accountId(),
            registeredBy: $importation->registeredBy()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterImportationRequest;
    }
}
