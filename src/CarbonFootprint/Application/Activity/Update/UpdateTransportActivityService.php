<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateTransportActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
        private TransportEmissionFactorSelector $factorSelector,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(UpdateTransportActivityRequest $request, User $user): UpdateTransportActivityResponse
    {
        $activity = $this->activityRepository->findById($request->activityId);

        if (!$activity instanceof TransportActivity) {
            throw new ActivityNotFoundException($request->activityId);
        }

        $this->authorizer->denyUnlessCanDoThat($activity, SecurityAction::Edit, $user);

        $emissionFactor = $this->factorSelector->select((string) $request->transportId, $request->year, null, $request->emissionFactorGroupId);
        $emissions = $this->calculator->calculate($emissionFactor, $request->quantity);

        $activity->update(
            transportId: $request->transportId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $request->quantity,
            source: $request->source,
            weight: $request->weight,
            distanceKm: $request->distanceKm,
        );

        $this->activityRepository->save($activity);

        return new UpdateTransportActivityResponse(activityId: $request->activityId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateTransportActivityRequest;
    }
}
