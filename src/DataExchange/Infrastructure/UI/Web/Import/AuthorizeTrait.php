<?php

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\Importation\ImportationType;
use App\DataExchange\Domain\Model\Importation\ImportationTypeFactory;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

trait AuthorizeTrait
{
    public function __construct(
        private ImportationAuthorizer $importationAuthorizer,
        private ImportationTypeFactory $importationTypeFactory,
        private ImportationRepository $importationRepository,
    ) {

    }

    private function getImportationOrNotFound(string $id): Importation
    {
        $importationId = new ImportationId($id);
        $importation = $this->importationRepository->find($importationId);

        if (null === $importation) {
            throw $this->createNotFoundException();
        }

        return $importation;
    }

    private function getImportationTypeOrNotFound(string $code): ImportationType
    {
        $importationType = $this->importationTypeFactory->createFromCode($code);

        if (null === $importationType) {
            throw $this->createNotFoundException();
        }

        return $importationType;
    }

    private function denyAccessUnlessUserIsGranted(User $user, Importation $importation, ImportationType $importationType): void
    {
        if (false === $this->isUserGranted($user, $importation, $importationType)) {
            throw $this->createAccessDeniedException();
        }
    }

    private function isUserGranted(User $user, Importation $importation, ImportationType $importationType): bool
    {
        if ($importation->type() !== $importationType->code()) {
            return false;
        }

        if (false === $importationType->isAuthorized($user)) {
            return false;
        }
        return false !== $this->importationAuthorizer->canDoThat(
                $importation, Action::Edit, $user
            );
    }
}
