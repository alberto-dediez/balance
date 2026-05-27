<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Register;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterSurplusService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private SurplusAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterSurplusRequest $request, User $user): RegisterSurplusResponse
    {
        $id = SurplusId::generate();

        assert($request->serviceCategoryId instanceof ServiceCategoryId);
        assert($request->criticalPointId instanceof CriticalPointId);
        assert($request->surplusCauseId instanceof SurplusCauseId);
        assert($request->accountId instanceof AccountId);
        assert($request->registeredBy instanceof UserId);

        $subject = new Surplus(id: $id, accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($subject, SecurityAction::Register, $user);

        $surplus = new Surplus($id, accountId: $request->accountId);
        $surplus->register(
            date: $request->date,
            serviceCategoryId: $request->serviceCategoryId,
            criticalPointId: $request->criticalPointId,
            surplusCauseId: $request->surplusCauseId,
            foodType: $request->foodType,
            quantity: $request->quantity,
            unit: $request->unit,
            accountId: $request->accountId,
            registeredBy: $request->registeredBy,
            incidenceId: $request->incidenceId,
        );

        $this->surplusRepository->add($surplus);

        return new RegisterSurplusResponse(
            surplusId: $surplus->id(),
            date: $surplus->date(),
            foodType: $surplus->foodType(),
            quantity: $surplus->quantity(),
            unit: $surplus->unit(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterSurplusRequest;
    }
}
