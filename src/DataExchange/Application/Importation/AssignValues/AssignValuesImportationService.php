<?php

namespace App\DataExchange\Application\Importation\AssignValues;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignValuesImportationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private ImportationRepository $importationRepository, private ImportationAuthorizer $authorizer)
    {
    }

    public function __invoke(AssignValuesImportationRequest $request, User $user): AssignValuesImportationResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (null === $importation) {
            throw new \RuntimeException('Importation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($importation, Action::Edit, $user);

        $importation->setDefaultValues(
            $request->defaultValues,
        );

        $this->importationRepository->add($importation);

        return new AssignValuesImportationResponse(
            importationId: $importation->id(),
            defaultValues: $importation->defaultValues(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignValuesImportationRequest;
    }
}
