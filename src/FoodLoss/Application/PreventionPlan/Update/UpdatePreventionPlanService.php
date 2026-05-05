<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Update;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdatePreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdatePreventionPlanRequest $request, User $user): UpdatePreventionPlanResponse
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (null == $preventionPlan) {
            throw new \InvalidArgumentException('Prevention plan not found');
        }

        assert($request->companyId instanceof CompanyId);

        $this->authorizer->denyUnlessCanDoThat($preventionPlan, SecurityAction::Edit, $user);

        $preventionPlan->update(
            name: $request->name,
            propertyIds: $request->properties,
            companyId: $request->companyId,
            updatedBy: $user->id(),
            responsible: $request->responsible,
        );

        $this->preventionPlanRepository->add($preventionPlan);

        return new UpdatePreventionPlanResponse(
            preventionPlanId: $preventionPlan->id(),
            name: $preventionPlan->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePreventionPlanRequest;
    }
}
