<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\GetByToken;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;

final class GetEmployeeContributionByTokenService
{
    public function __construct(
        private EmployeeContributionAuthenticator $authenticator,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventRepository $eventRepository,
        private MobilityRepository $mobilityRepository,
    ) {}

    public function __invoke(GetEmployeeContributionByTokenRequest $request): GetEmployeeContributionByTokenResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $employeeContribution = $authentication->employeeContribution;

        $eventCarbonFootprintId = $employeeContribution->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;

        if (null === $eventCarbonFootprint) {
            throw new EmployeeContributionTokenInvalidException();
        }

        $eventId = $eventCarbonFootprint->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        if (null === $event) {
            throw new EmployeeContributionTokenInvalidException();
        }

        $mobilities = $this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0));

        return new GetEmployeeContributionByTokenResponse($employeeContribution, $eventCarbonFootprint, $event, $mobilities);
    }
}
