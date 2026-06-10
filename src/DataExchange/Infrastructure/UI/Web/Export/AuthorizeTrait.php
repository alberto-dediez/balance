<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Export;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportationTypeFactory;
use App\Security\Domain\Model\User\User;

trait AuthorizeTrait
{
    public function __construct(
        private ExportationTypeFactory $exportationTypeFactory,
        private ExportationRepository $exportationRepository,
    ) {}

    private function getExportationTypeOrNotFound(string $code): ExportationType
    {
        $exportationType = $this->exportationTypeFactory->createFromCode($code);

        if (null === $exportationType) {
            throw $this->createNotFoundException();
        }

        return $exportationType;
    }

    private function getExportationOrNotFound(string $id): Exportation
    {
        $exportation = $this->exportationRepository->find(new ExportationId($id));

        if (null === $exportation) {
            throw $this->createNotFoundException();
        }

        return $exportation;
    }

    private function denyUnlessTypeAllowed(ExportationType $exportationType, User $user): void
    {
        if (false === $exportationType->isAuthorized($user)) {
            throw $this->createAccessDeniedException();
        }
    }
}
