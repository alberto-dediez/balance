<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Register;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterPreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterPreventionPlanRequest $request, User $user): RegisterPreventionPlanResponse
    {
        $id = PreventionPlanId::generate();

        assert($request->companyId instanceof CompanyId);
        assert($request->accountId instanceof AccountId);

        $this->authorizer->denyUnlessCanDoThat(
            new PreventionPlan(id: $id, accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $preventionPlan = new PreventionPlan($id);
        $preventionPlan->register(
            name: $request->name,
            propertyIds: $request->properties,
            companyId: $request->companyId,
            accountId: $request->accountId,
            registeredBy: $user->id(),
        );

        $this->preventionPlanRepository->add($preventionPlan);

        return new RegisterPreventionPlanResponse(
            preventionPlanId: $preventionPlan->id(),
            name: $preventionPlan->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPreventionPlanRequest;
    }
}
