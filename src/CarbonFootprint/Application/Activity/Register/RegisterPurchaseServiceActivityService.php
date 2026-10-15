<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterPurchaseServiceActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private PurchaseServiceEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(RegisterPurchaseServiceActivityRequest $request, User $user): RegisterPurchaseServiceActivityResponse
    {
        $id = ActivityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new PurchaseServiceActivity(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->purchaseServiceId, $request->year, null, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity = PurchaseServiceActivity::register(
            id: $id,
            accountId: $request->accountId,
            purchaseServiceId: $request->purchaseServiceId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
        );

        $this->activityRepository->save($activity);

        return new RegisterPurchaseServiceActivityResponse(activityId: $id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPurchaseServiceActivityRequest;
    }
}
