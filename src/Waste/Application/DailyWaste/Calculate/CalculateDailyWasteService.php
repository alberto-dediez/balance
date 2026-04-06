<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteRepository;

class CalculateDailyWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DailyWasteRepository $dailyWasteRepository,
        private ContainerRepository $containerRepository,
        private MeasurementRepository $measurementRepository,
        private PropertyRepository $propertyRepository,
        private WasteRepository $wasteRepository,
        private DailyWasteAuthorizer $authorizer,
    ) {}

    public function __invoke(CalculateDailyWasteRequest $request, User $user): CalculateDailyWasteResponse
    {
        $this->validateRequest($request);

        $id = DailyWasteId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new DailyWaste(
                id: $id,
                propertyId: $request->propertyId,
                accountId: $request->accountId,
                day: $request->day,
            ),
            Action::Register,
            $user,
        );

        $load = $this->calculateLoad($request);

        $dailyWaste = $this->dailyWasteRepository->findByDay(
            day: $request->day,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            wasteManagerId: $request->wasteManagerId,
            accountId: $request->accountId,
        );

        if (!$dailyWaste instanceof DailyWaste) {
            $dailyWaste = new DailyWaste($id);
            $dailyWaste
                ->register(
                    propertyId: $request->propertyId,
                    wasteId: $request->wasteId,
                    purpose: $request->purpose,
                    wasteManagerId: $request->wasteManagerId,
                    day: $request->day,
                    load: $load,
                    accountId: $request->accountId,
                )
            ;
        } else {
            $dailyWaste->update($load);
        }

        $this->dailyWasteRepository->add($dailyWaste);

        return new CalculateDailyWasteResponse(
            dailyWasteId: $dailyWaste->id(),
            load: $dailyWaste->load(),
        );
    }

    private function validateRequest(CalculateDailyWasteRequest $request): void
    {
        $property = $this->propertyRepository->find($request->propertyId);

        if (!$property instanceof Property || !$property->accountId() instanceof AccountId) {
            throw new \InvalidArgumentException('Debes especificar una propiedad válida');
        }

        if (false === $property->accountId()->equals($request->accountId)) {
            throw new \InvalidArgumentException('Debes especificar una propiedad válida');
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
            accountId: $request->accountId,
        );

        if (0 === count($measurements)) {
            return 0.0;
        }

        $wasteRepository = $this->wasteRepository;
        $containerRepository = $this->containerRepository;

        return array_reduce($measurements, function (float $totalLoad, Measurement $measurement) use ($wasteRepository, $containerRepository): float {
            assert($measurement->unit() instanceof Unit);
            if (Unit::Kilogram !== $measurement->unit()) {
                assert($measurement->containerId() instanceof ContainerId);
                $container = $containerRepository->find($measurement->containerId());
                $containerVolume = $container instanceof Container ? $container->volume() : 0;

                assert($measurement->wasteId() instanceof WasteId);
                $waste = $wasteRepository->find($measurement->wasteId());
                $wasteDensity = $waste instanceof Waste ? $waste->density() : 0;

                $load = $measurement->unit()->calculateLoadInKilograms(
                    load: $measurement->load(),
                    volume: floatval($containerVolume),
                    density: floatval($wasteDensity),
                );
            } else {
                $load = $measurement->load();
            }

            return $totalLoad + $load * $measurement->quantity();
        }, 0.0);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CalculateDailyWasteRequest;
    }
}
