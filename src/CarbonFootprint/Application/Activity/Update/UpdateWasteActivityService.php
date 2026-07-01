<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\WasteEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateWasteActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private WasteEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(UpdateWasteActivityRequest $request, User $user): UpdateWasteActivityResponse
    {
        $activity = $this->activityRepository->findById($request->activityId);

        if (!$activity instanceof WasteActivity) {
            throw new ActivityNotFoundException($request->activityId);
        }

        $this->authorizer->denyUnlessCanDoThat($activity, SecurityAction::Edit, $user);

        $emissionFactor = $this->factorSelector->select($request->wasteId, $request->year, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity->update(
            wasteId: $request->wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
        );

        $this->activityRepository->save($activity);

        return new UpdateWasteActivityResponse(activityId: $request->activityId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateWasteActivityRequest;
    }
}
