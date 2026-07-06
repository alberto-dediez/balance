<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateMaterialActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private MaterialEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(UpdateMaterialActivityRequest $request, User $user): UpdateMaterialActivityResponse
    {
        $activity = $this->activityRepository->findById($request->activityId);

        if (!$activity instanceof MaterialActivity) {
            throw new ActivityNotFoundException($request->activityId);
        }

        $this->authorizer->denyUnlessCanDoThat($activity, SecurityAction::Edit, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->materialId, $request->year, $request->condition, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity->update(
            materialId: $request->materialId,
            condition: $request->condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
        );

        $this->activityRepository->save($activity);

        return new UpdateMaterialActivityResponse(activityId: $request->activityId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialActivityRequest;
    }
}
