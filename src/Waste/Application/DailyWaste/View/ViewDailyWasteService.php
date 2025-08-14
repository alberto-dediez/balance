<?php

namespace App\Waste\Application\DailyWaste\View;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class ViewDailyWasteService
{
    public function __construct(private DailyWasteRepository $dailyWasteRepository, private MeasurementRepository $measurementRepository, private DailyWasteAuthorizer $authorizer)
    {

    }

    public function __invoke(ViewDailyWasteRequest $request, User $user): ViewDailyWasteResponse
    {
        $dailyWaste = $this->dailyWasteRepository->find($request->dailyWasteId);

        if (null === $dailyWaste) {
            throw new \InvalidArgumentException("Daily waste not found!");
        }

        $this->authorizer->denyUnlessCanDoThat($dailyWaste, Action::View, $user);

        $measurements = $this->measurementRepository->findByDay(
            day: $dailyWaste->day(),
            propertyId: $dailyWaste->propertyId(),
            wasteId: $dailyWaste->wasteId(),
            purpose: $dailyWaste->purpose(),
            wasteManagerId: $dailyWaste->wasteManagerId(),
            accountId: $dailyWaste->accountId()
        );

        return new ViewDailyWasteResponse(
            measurements: $measurements, 
            dailyWaste: $dailyWaste
        );
    }
}