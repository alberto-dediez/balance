<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterCombustibleActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private CombustibleEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(RegisterCombustibleActivityRequest $request, User $user): RegisterCombustibleActivityResponse
    {
        $id = ActivityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CombustibleActivity(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->combustibleId, $request->year, null, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity = CombustibleActivity::register(
            id: $id,
            accountId: $request->accountId,
            combustibleId: $request->combustibleId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
            source: $request->source,
        );

        $this->activityRepository->save($activity);

        return new RegisterCombustibleActivityResponse(activityId: $id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCombustibleActivityRequest;
    }
}
