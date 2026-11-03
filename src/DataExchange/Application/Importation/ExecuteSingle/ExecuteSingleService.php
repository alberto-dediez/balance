<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\ExecuteSingle;

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
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ExecuteSingleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public const string INTERRUPTED_ERROR = 'Ejecución interrumpida en un intento anterior: comprueba si la fila se ha importado antes de repetirla';

    public function __construct(
        private ImportationRepository $importationRepository,
        private ImportationTypeFactory $importationTypeFactory,
        private ImportationAuthorizer $authorizer,
    ) {}

    public function __invoke(ExecuteSingleRequest $request, User $user): ExecuteSingleResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (!$importation instanceof Importation) {
            throw new \RuntimeException('Importation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($importation, Action::View, $user);

        $importationType = $this->importationTypeFactory->createFromCode($importation->type());

        if (!$importationType instanceof ImportationType) {
            throw new \RuntimeException('Import type not found');
        }

        if (false === $importationType->isAuthorized($user)) {
            throw new \RuntimeException('Import type not allowed');
        }

        $errorMessage = $this->importationRepository->claimRow($importation->id(), $request->rowNumber)
            ? $this->executeRow($importation, $request, $user)
            : $this->resultOfRetriedRow($importation, $request);

        $pending = $this->importationRepository->pendingRows($importation->id());

        if (0 === $pending && !$importation->isExecuted() && $this->importationRepository->claimExecution($importation->id())) {
            $importation->finishExecution();

            $this->importationRepository->add($importation);
        }

        return new ExecuteSingleResponse(
            importationId: $importation->id(),
            rowNumber: $request->rowNumber,
            success: null === $errorMessage,
        );
    }

    private function executeRow(Importation $importation, ExecuteSingleRequest $request, User $user): ?string
    {
        $errorMessage = null;

        try {
            CommandBusDispatcher::instance()->dispatch(
                $request->command,
                $user,
            );
        } catch (\Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        $this->importationRepository->saveResult($importation->id(), $request->rowNumber, $errorMessage);

        return $errorMessage;
    }

    private function resultOfRetriedRow(Importation $importation, ExecuteSingleRequest $request): ?string
    {
        $result = $this->importationRepository->getResult($importation->id(), $request->rowNumber);

        if (null === $result) {
            throw new \RuntimeException('Importation row not found');
        }

        if (!(bool) ($result['executed'] ?? false)) {
            $this->importationRepository->saveResult($importation->id(), $request->rowNumber, self::INTERRUPTED_ERROR);

            return self::INTERRUPTED_ERROR;
        }

        $error = $result['error'] ?? null;

        return is_string($error) ? $error : null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExecuteSingleRequest;
    }
}
