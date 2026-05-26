<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Transformation;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssessTransformationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private SurplusAuthorizer $authorizer,
    ) {}

    public function __invoke(AssessTransformationRequest $request, User $user): AssessTransformationResponse
    {
        $surplus = $this->surplusRepository->find($request->surplusId);

        if (null === $surplus) {
            throw new \InvalidArgumentException('Surplus not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplus, SecurityAction::Edit, $user);

        $surplus->assessTransformation(
            canBeTransformed: $request->canBeTransformed,
            reasonId: !$request->canBeTransformed ? $request->reasonId : null,
            product: $request->canBeTransformed ? $request->product : null,
            channel: $request->canBeTransformed ? $request->channel : null,
        );

        $this->surplusRepository->add($surplus);

        return new AssessTransformationResponse(surplusId: $surplus->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssessTransformationRequest;
    }
}
