<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\Register;

use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterActionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActionRepository $actionRepository,
        private ActionAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterActionRequest $request, User $user): RegisterActionResponse
    {
        $id = ActionId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Action(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $action = new Action($id);
        $action->register(
            $request->description,
            $request->priority,
            $isDefaultLocale ? null : $locale,
        );

        $this->actionRepository->add($action);

        return new RegisterActionResponse(
            actionId: $action->id(),
            description: $action->description(),
            priority: $action->priority(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterActionRequest;
    }
}
