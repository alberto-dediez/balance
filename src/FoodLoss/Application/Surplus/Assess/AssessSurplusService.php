<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssessSurplusService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private SurplusAuthorizer $authorizer,
    ) {}

    public function __invoke(AssessSurplusRequest $request, User $user): AssessSurplusResponse
    {
        $surplus = $this->surplusRepository->find($request->surplusId);

        if (null === $surplus) {
            throw new \InvalidArgumentException('Surplus not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplus, SecurityAction::Edit, $user);

        match ($request->destination) {
            SurplusDestination::Transformation => $surplus->transformationAssess(),
            SurplusDestination::HumanDonation => $surplus->humanDonationAssess(),
            SurplusDestination::AnimalFeed => $surplus->animalFeedAssess(),
            SurplusDestination::Composting => $surplus->compostingAssess(),
            SurplusDestination::Elimination => $surplus->eliminationAssess(),
        };

        $this->surplusRepository->add($surplus);

        return new AssessSurplusResponse(
            surplusId: $surplus->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssessSurplusRequest;
    }
}
