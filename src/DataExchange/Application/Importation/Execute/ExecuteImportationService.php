<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute;

use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleRequest;
use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\Importation\ImportationType;
use App\DataExchange\Domain\Model\Importation\ImportationTypeFactory;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ExecuteImportationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ImportationRepository $importationRepository,
        private ImportationTypeFactory $importationTypeFactory,
        private ImportationAuthorizer $authorizer,
    ) {}

    public function __invoke(ExecuteImportationRequest $request, User $user): ExecuteImportationResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (!$importation instanceof Importation) {
            throw new \RuntimeException('Importation not found');
        }

        $importationType = $this->importationTypeFactory->createFromCode($importation->type());

        if (!$importationType instanceof ImportationType) {
            throw new \RuntimeException('Import type not found');
        }

        $this->authorizer->denyUnlessCanDoThat($importation, Action::Edit, $user);

        $importation->startExecution();
        $this->importationRepository->add($importation);

        $rowNumber = 0;
        $rowExtractor = $importationType->buildRowExtractor($importation);
        while (null !== ($row = $this->importationRepository->getData($importation->id(), $rowNumber))) {
            $data = $rowExtractor->extractFields($row);

            $command = $importationType->commandBuilder($data);

            $errorMessage = null;

            try {
                $response = CommandBusDispatcher::instance()->dispatch(
                    new DeferredCommand(
                        new ExecuteSingleRequest(
                            importationId: $importation->id(),
                            rowNumber: $rowNumber,
                            command: $command,
                        ),
                    ),
                    $user,
                );
            } catch (\Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }

            $this->importationRepository->saveResult($importation->id(), $rowNumber, $errorMessage);

            ++$rowNumber;
        }

        return new ExecuteImportationResponse(
            importationId: $importation->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExecuteImportationRequest;
    }
}
