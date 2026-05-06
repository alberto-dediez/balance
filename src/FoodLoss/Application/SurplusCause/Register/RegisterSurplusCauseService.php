<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Register;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterSurplusCauseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusCauseRepository $surplusCauseRepository,
        private SurplusCauseAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterSurplusCauseRequest $request, User $user): RegisterSurplusCauseResponse
    {
        $id = SurplusCauseId::generate();

        $this->authorizer->denyUnlessCanDoThat(new SurplusCause(id: $id), Action::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $surplusCause = new SurplusCause($id);
        $surplusCause->register(
            $request->name,
            $isDefaultLocale ? null : $locale,
        );

        $this->surplusCauseRepository->add($surplusCause);

        return new RegisterSurplusCauseResponse(
            surplusCauseId: $surplusCause->id(),
            name: $surplusCause->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterSurplusCauseRequest;
    }
}
