<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CateringItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterCateringItemActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private CateringItemEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(RegisterCateringItemActivityRequest $request, User $user): RegisterCateringItemActivityResponse
    {
        $id = ActivityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CateringItemActivity(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->cateringItemId, $request->year, $request->condition, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity = CateringItemActivity::register(
            id: $id,
            accountId: $request->accountId,
            cateringItemId: $request->cateringItemId,
            condition: $request->condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
        );

        $this->activityRepository->save($activity);

        return new RegisterCateringItemActivityResponse(activityId: $id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCateringItemActivityRequest;
    }
}
