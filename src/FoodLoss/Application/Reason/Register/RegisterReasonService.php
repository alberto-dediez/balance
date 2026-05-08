<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Register;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterReasonService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ReasonRepository $reasonRepository,
        private ReasonAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterReasonRequest $request, User $user): RegisterReasonResponse
    {
        $id = ReasonId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Reason(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $reason = new Reason($id);
        $reason->register(
            $request->description,
            $isDefaultLocale ? null : $locale,
        );

        $this->reasonRepository->add($reason);

        return new RegisterReasonResponse(
            reasonId: $reason->id(),
            description: $reason->description(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterReasonRequest;
    }
}
