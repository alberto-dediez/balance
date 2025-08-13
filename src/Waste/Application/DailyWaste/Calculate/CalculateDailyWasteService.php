<?php

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class CalculateDailyWasteService
{
    public function __construct(
        private DailyWasteRepository $dailyWasteRepository,
        private ContainerRepository $containerRepository,
        private MeasurementRepository $measurementRepository,
        private PropertyRepository $propertyRepository,
        private WasteRepository $wasteRepository,
        private DailyWasteAuthorizer $authorizer
    ) {

    }

    public function __invoke(CalculateDailyWasteRequest $request, User $user): CalculateDailyWasteResponse
    {
        $this->validateRequest($request);

        $id = DailyWasteId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new DailyWaste(
                id: $id,
                propertyId: $request->propertyId,
                accountId: $request->accountId,
                day: $request->day
            ),
            Action::Register,
            $user
        );

        $load = $this->calculateLoad($request);

        $dailyWaste = $this->dailyWasteRepository->findByDay(
            day: $request->day,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            wasteManagerId: $request->wasteManagerId,
            accountId: $request->accountId
        );

        if (null === $dailyWaste) {
            $dailyWaste = new DailyWaste($id);
            $dailyWaste
                ->register(
                    day: $request->day,
                    propertyId: $request->propertyId,
                    wasteId: $request->wasteId,
                    purpose: $request->purpose,
                    wasteManagerId: $request->wasteManagerId,
                    accountId: $request->accountId,
                    load: $load
                )
            ;
        } else {
            $dailyWaste->update($load);
        }

        $this->dailyWasteRepository->add($dailyWaste);

        return new CalculateDailyWasteResponse(
            dailyWasteId: $dailyWaste->id(),
            load: $dailyWaste->load()
        );
    }

    private function validateRequest(CalculateDailyWasteRequest $request)
    {
        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property || false === $property->accountId()->equals($request->accountId)) {
            throw new \InvalidArgumentException("Debes especificar una propiedad válida");
        }
    }

    private function calculateLoad(CalculateDailyWasteRequest $request): float
    {
        $measurements = $this->measurementRepository->findByDay(
            day: $request->day,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            wasteManagerId: $request->wasteManagerId,
            accountId: $request->accountId
        );

        if (null === $measurements || 0 === count($measurements)) {
            return 0.0;
        }

        $wasteRepository = $this->wasteRepository;
        $containerRepository = $this->containerRepository;

        return array_reduce($measurements, function (float $totalLoad, Measurement $measurement) use ($wasteRepository, $containerRepository) {
            if ($measurement->measurementMethod() === MeasurementMethod::Volume) {
                $container = $containerRepository->find($measurement->containerId());
                $containerVolume = null === $container ? 0 : $container->volume();

                $waste = $wasteRepository->find($measurement->wasteId());
                $wasteDensity = null === $waste ? 0 : $waste->density();

                $load = ($measurement->load() / 100) * ($containerVolume * $wasteDensity);
            } else {
                $load = $measurement->load();
            }

            return $totalLoad + $load;
        }, 0.0);
    }
}