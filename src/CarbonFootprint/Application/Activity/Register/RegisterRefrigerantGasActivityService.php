<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterRefrigerantGasActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private RefrigerantGasEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(RegisterRefrigerantGasActivityRequest $request, User $user): RegisterRefrigerantGasActivityResponse
    {
        $id = ActivityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new RefrigerantGasActivity(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->refrigerantGasId, $request->year, null, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity = RefrigerantGasActivity::register(
            id: $id,
            accountId: $request->accountId,
            refrigerantGasId: $request->refrigerantGasId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
            source: $request->source,
        );

        $this->activityRepository->save($activity);

        return new RegisterRefrigerantGasActivityResponse(activityId: $id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterRefrigerantGasActivityRequest;
    }
}
