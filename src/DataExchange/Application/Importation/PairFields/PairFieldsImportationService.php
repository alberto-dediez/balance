<?php

namespace App\DataExchange\Application\Importation\PairFields;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PairFieldsImportationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private ImportationRepository $importationRepository, private ImportationAuthorizer $authorizer)
    {
    }

    public function __invoke(PairFieldsImportationRequest $request, User $user): PairFieldsImportationResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (null === $importation) {
            throw new \RuntimeException('Importation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($importation, Action::Edit, $user);

        $importation->configure(
            $request->configurations
        );

        $this->importationRepository->add($importation);

        return new PairFieldsImportationResponse(
            importationId: $importation->id(),
            configurations: $importation->configurations()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PairFieldsImportationRequest;
    }
}
