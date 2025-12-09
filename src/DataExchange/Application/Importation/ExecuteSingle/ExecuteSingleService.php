<?php

namespace App\DataExchange\Application\Importation\ExecuteSingle;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\Importation\ImportationTypeFactory;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ExecuteSingleService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(
        private ImportationRepository $importationRepository,
        private ImportationTypeFactory $importationTypeFactory,
        private ImportationAuthorizer $authorizer
    ) {
    }

    public function __invoke(ExecuteSingleRequest $request, User $user): ExecuteSingleResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (null === $importation) {
            throw new \RuntimeException('Importation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($importation, Action::View, $user);

        $importationType = $this->importationTypeFactory->createFromCode($importation->type());

        if (null === $importationType) {
            throw new \RuntimeException('Import type not found');
        }

        if (false === $importationType->isAuthorized($user)) {
            throw new \RuntimeException('Import type not allowed');
        }

        $errorMessage = null;

        try {
            CommandBusDispatcher::instance()->dispatch(
                $request->command,
                $user
            );
        } catch (\Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        $this->importationRepository->saveResult($importation->id(), $request->rowNumber, $errorMessage);

        $pending = $this->importationRepository->pendingRows($importation->id());

        if (0 === $pending && !$importation->isExecuted()) {
            $importation->finishExecution();

            $this->importationRepository->add($importation);
        }

        return new ExecuteSingleResponse(
            importationId: $importation->id(),
            rowNumber: $request->rowNumber,
            success: null === $errorMessage
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExecuteSingleRequest;
    }
}
