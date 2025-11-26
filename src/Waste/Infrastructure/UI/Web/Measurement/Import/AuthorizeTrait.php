<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\Security\Application\Action;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;

trait AuthorizeTrait
{
    private function denyAccessUnlessGrantedToView(?Importation $importation): void
    {
        if (false === $this->amIAuthorizedToView($importation)) {
            throw $this->createAccessDeniedException();
        }
    }

    private function amIAuthorizedToView(?Importation $importation): bool
    {
        if (null === $importation) {
            return false;
        }

        $user = $this->loggedUser->__invoke();
        $account = $this->loggedAccount->__invoke();

        $isAuthorizedToRegisterMeasurements = $this->measurementAuthorizer->canDoThat(
            new Measurement(
                id: MeasurementId::generate(),
                accountId: $account?->id()
            ),
            Action::Import,
            $user
        );

        $isAuthorizedToImportation = null !== $this->importationAuthorizer->canDoThat(
                $importation, Action::View, $user
            );

        return $isAuthorizedToImportation && $isAuthorizedToRegisterMeasurements;
    }
}
