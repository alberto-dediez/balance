<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Register;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterCombustibleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CombustibleRepository $combustibleRepository,
        private CombustibleAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCombustibleRequest $request, User $user): RegisterCombustibleResponse
    {
        $id = CombustibleId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Combustible(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $combustible = new Combustible($id);
        $combustible->register(
            $request->name,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->combustibleRepository->add($combustible);

        return new RegisterCombustibleResponse(
            combustibleId: $combustible->id(),
            name: $combustible->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCombustibleRequest;
    }
}
