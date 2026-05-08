<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Update;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateReasonService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ReasonRepository $reasonRepository,
        private ReasonAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateReasonRequest $request, User $user): UpdateReasonResponse
    {
        $reason = $this->reasonRepository->find($request->reasonId);

        if (null == $reason) {
            throw new \InvalidArgumentException('Reason not found');
        }

        $this->authorizer->denyUnlessCanDoThat($reason, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $reason->update(
            description: $request->description,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->reasonRepository->add($reason);

        return new UpdateReasonResponse(
            reasonId: $reason->id(),
            description: $reason->description(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateReasonRequest;
    }
}
