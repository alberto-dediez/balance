<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateElectricityActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private ElectricityEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
        private ElectricityMixEmissionsResolver $mixResolver,
    ) {}

    public function __invoke(UpdateElectricityActivityRequest $request, User $user): UpdateElectricityActivityResponse
    {
        $activity = $this->activityRepository->findById($request->activityId);

        if (!$activity instanceof ElectricityActivity) {
            throw new ActivityNotFoundException($request->activityId);
        }

        $this->authorizer->denyUnlessCanDoThat($activity, SecurityAction::Edit, $user);

        $emissionFactor = $this->factorSelector->select(
            null !== $request->electricityMarketerId ? (string) $request->electricityMarketerId : '',
            $request->year,
            null,
            $request->emissionFactorGroupId,
            null !== $request->gdOFactorId ? (string) $request->gdOFactorId : null,
        );
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);
        $wellToTankEmissionFactor = $this->factorSelector->selectWellToTank(
            null !== $request->electricityMarketerId ? (string) $request->electricityMarketerId : '',
            $request->year,
            $request->emissionFactorGroupId,
            null !== $request->gdOFactorId ? (string) $request->gdOFactorId : null,
        );
        $wellToTankEmissions = $this->calculator->calculate($wellToTankEmissionFactor, $request->quantity);

        $mix = $this->mixResolver->resolve($request->countryId, $request->year, $request->emissionFactorGroupId, $request->quantity);

        $activity->update(
            electricityMarketerId: $request->electricityMarketerId,
            gdOFactorId: $request->gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
            source: $request->source,
            electricityMixId: $mix?->electricityMixId,
            locationBasedEmissionFactor: $mix?->emissionFactor,
            locationBasedEmissions: $mix?->emissions,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
            locationBasedWellToTankEmissionFactor: $mix?->wellToTankEmissionFactor,
            locationBasedWellToTankEmissions: $mix?->wellToTankEmissions,
        );

        $this->activityRepository->save($activity);

        return new UpdateElectricityActivityResponse(activityId: $request->activityId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateElectricityActivityRequest;
    }
}
