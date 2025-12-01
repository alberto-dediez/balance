<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;

trait AuthorizeTrait
{
    public function __construct(
        private LoggedUser $loggedUser,
        private LoggedAccount $loggedAccount,
        private MeasurementAuthorizer $measurementAuthorizer,
        private ImportationAuthorizer $importationAuthorizer,
    ) {

    }

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

        $isAuthorizedToImportation = $this->importationAuthorizer->canDoThat(
            $importation, Action::View, $user
        );

        return $isAuthorizedToImportation && $isAuthorizedToRegisterMeasurements;
    }
}
