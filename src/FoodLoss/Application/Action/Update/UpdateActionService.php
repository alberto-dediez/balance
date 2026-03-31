<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\Update;

use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateActionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActionRepository $actionRepository,
        private ActionAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateActionRequest $request, User $user): UpdateActionResponse
    {
        $action = $this->actionRepository->find($request->actionId);

        if (null == $action) {
            throw new \InvalidArgumentException('Action not found');
        }

        $this->authorizer->denyUnlessCanDoThat($action, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $action->update(
            description: $request->description,
            priority: $request->priority,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->actionRepository->add($action);

        return new UpdateActionResponse(
            actionId: $action->id(),
            description: $action->description(),
            priority: $action->priority()->value,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateActionRequest;
    }
}
