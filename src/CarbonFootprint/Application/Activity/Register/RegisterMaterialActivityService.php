<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterMaterialActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private MaterialEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(RegisterMaterialActivityRequest $request, User $user): RegisterMaterialActivityResponse
    {
        $id = ActivityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MaterialActivity(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->materialId, $request->year, $request->condition, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity = MaterialActivity::register(
            id: $id,
            accountId: $request->accountId,
            materialId: $request->materialId,
            condition: $request->condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
        );

        $this->activityRepository->save($activity);

        return new RegisterMaterialActivityResponse(activityId: $id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMaterialActivityRequest;
    }
}
