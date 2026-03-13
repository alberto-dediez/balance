<?php

namespace App\FoodLoss\Application\Action\Update;

use InvalidArgumentException;
use App\Security\Application\Action as SecurityACtion;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Security\Application\Account\Register\RegisterAccountRequest;

class UpdateActionService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(
        private ActionRepository $actionRepository,
        private LocaleGetter $localeGetter,
        private ActionAuthorizer $authorizer
    ) {

    }

    public function __invoke(UpdateActionRequest $request, User $user): UpdateActionResponse
    {
        $action = $this->actionRepository->find($request->actionId);
        
        if (null == $action) {
            throw new InvalidArgumentException("Action not found");
        }

        $this->authorizer->denyUnlessCanDoThat($action, SecurityAction::Edit, $user);

        $locale = $this->localeGetter->locale();
        $isDefaultLocale = $locale === $this->localeGetter->default();

        $action->update(
            description: $request->description,
            priority: $request->priority,
            locale: $isDefaultLocale ? null : $locale
        );

        $this->actionRepository->add($action);

        return new UpdateActionResponse(
            actionId: $action->id(),
            description: $action->description(),
            priority: $action->priority()->value
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateActionRequest;
    }
}