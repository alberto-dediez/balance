<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Exportation\StoredFile;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PurgeExpiredExportationsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly ExportationRepository $exportationRepository,
        private readonly ExportStorage $exportStorage,
    ) {}

    public function __invoke(PurgeExpiredExportationsRequest $request, User $user): PurgeExpiredExportationsResponse
    {
        $expired = $this->exportationRepository->findExpired($request->now);

        $purged = 0;

        foreach ($expired as $exportation) {
            $file = $exportation->file();

            if ($file instanceof StoredFile && $this->exportStorage->exists($file)) {
                $this->exportStorage->delete($file);
            }

            $exportation->expire();
            $this->exportationRepository->remove($exportation);

            ++$purged;
        }

        return new PurgeExpiredExportationsResponse(purged: $purged);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeExpiredExportationsRequest;
    }
}
