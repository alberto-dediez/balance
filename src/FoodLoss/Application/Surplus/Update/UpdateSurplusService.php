<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Update;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateSurplusService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private SurplusAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateSurplusRequest $request, User $user): UpdateSurplusResponse
    {
        $surplus = $this->surplusRepository->find($request->surplusId);

        if (null == $surplus) {
            throw new \InvalidArgumentException('Surplus not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplus, SecurityAction::Edit, $user);

        assert($request->serviceCategoryId instanceof ServiceCategoryId);
        assert($request->criticalPointId instanceof CriticalPointId);
        assert($request->surplusCauseId instanceof SurplusCauseId);

        $surplus->update(
            date: $request->date,
            serviceCategoryId: $request->serviceCategoryId,
            criticalPointId: $request->criticalPointId,
            surplusCauseId: $request->surplusCauseId,
            foodType: $request->foodType,
            quantity: $request->quantity,
            unit: $request->unit,
            preventionAssessment: $request->preventionAssessment,
            transformationAssessment: $request->transformationAssessment,
            humanDonationAssessment: $request->humanDonationAssessment,
            animalFeedAssessment: $request->animalFeedAssessment,
            compostingAssessment: $request->compostingAssessment,
            eliminationAssessment: $request->eliminationAssessment,
        );

        $this->surplusRepository->add($surplus);

        return new UpdateSurplusResponse(
            surplusId: $surplus->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateSurplusRequest;
    }
}
