<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Register;

use App\DataExchange\Application\Exportation\Execute\ExecuteExportationRequest;
use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportationTypeFactory;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterExportationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly ExportationRepository $exportationRepository,
        private readonly ExportationTypeFactory $exportationTypeFactory,
        private readonly int $validForDays = 7,
    ) {}

    public function __invoke(RegisterExportationRequest $request, User $user): RegisterExportationResponse
    {
        $exportationType = $this->exportationTypeFactory->createFromCode($request->type);

        if (!$exportationType instanceof ExportationType) {
            throw new \RuntimeException('Export type not found');
        }

        if (false === $exportationType->isAuthorized($user)) {
            throw new \RuntimeException('Export type not allowed');
        }

        $exportation = new Exportation(id: ExportationId::generate());

        $now = new \DateTimeImmutable('NOW');

        $exportation->register(
            type: $request->type,
            format: $request->format,
            columns: $request->columns,
            filterCriteria: $exportationType->dehydrateFilter($request->filter),
            validUntil: $now->add(new \DateInterval(sprintf('P%dD', $this->validForDays))),
            accountId: $request->accountId,
            registeredBy: $user->id(),
        );

        $this->exportationRepository->add($exportation);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new ExecuteExportationRequest(
                    exportationId: $exportation->id(),
                ),
                priority: Priority::LOW
            ),
            $user,
        );

        return new RegisterExportationResponse(
            exportationId: $exportation->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterExportationRequest;
    }
}
