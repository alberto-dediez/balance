<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Update;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateSurplusCauseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusCauseRepository $surplusCauseRepository,
        private SurplusCauseAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateSurplusCauseRequest $request, User $user): UpdateSurplusCauseResponse
    {
        $surplusCause = $this->surplusCauseRepository->find($request->surplusCauseId);

        if (null == $surplusCause) {
            throw new \InvalidArgumentException('Surplus cause not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplusCause, Action::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $surplusCause->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->surplusCauseRepository->add($surplusCause);

        return new UpdateSurplusCauseResponse(
            surplusCauseId: $surplusCause->id(),
            name: $surplusCause->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateSurplusCauseRequest;
    }
}
