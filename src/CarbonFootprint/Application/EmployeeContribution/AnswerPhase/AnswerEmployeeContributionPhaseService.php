<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class AnswerEmployeeContributionPhaseService
{
    public function __construct(
        private EmployeeContributionAuthenticator $authenticator,
        private EmployeeContributionRepository $employeeContributionRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private string $consentPolicyVersion,
    ) {}

    public function __invoke(AnswerEmployeeContributionPhaseRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $employeeContribution = $authentication->employeeContribution;

        $eventCarbonFootprintId = $employeeContribution->eventCarbonFootprintId();

        if (null === $eventCarbonFootprintId) {
            throw new EmployeeContributionTokenInvalidException();
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new EmployeeContributionTokenInvalidException();
        }

        if ($eventCarbonFootprint->status()->isCompleted()) {
            throw new EmployeeContributionClosedException();
        }

        if ($request->consentGranted) {
            $employeeContribution->grantConsent(new ConsentProof(
                consentedAt: new \DateTimeImmutable('NOW'),
                policyVersion: $this->consentPolicyVersion,
                locale: LocaleGetter::locale()->value,
                ip: $request->consentIp,
                userAgent: $request->consentUserAgent,
                acceptLanguage: $request->consentAcceptLanguage,
            ));
        }

        $employeeContribution->answerPhase(
            phase: $request->phase,
            worked: $request->worked,
            mobilityId: $request->mobilityId,
            distanceKm: $request->distanceKm,
            vehicleOccupants: $request->vehicleOccupants,
            days: $request->days,
            hotelNights: $request->hotelNights,
        );

        $this->employeeContributionRepository->add($employeeContribution);
    }
}
