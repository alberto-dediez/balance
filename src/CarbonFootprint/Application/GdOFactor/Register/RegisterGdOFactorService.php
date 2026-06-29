<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Register;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterGdOFactorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private GdOFactorRepository $gdOFactorRepository,
        private GdOFactorAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterGdOFactorRequest $request, User $user): RegisterGdOFactorResponse
    {
        $id = GdOFactorId::generate();

        $this->authorizer->denyUnlessCanDoThat(new GdOFactor(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $gdOFactor = new GdOFactor($id);
        $gdOFactor->register(
            $request->name,
            $isDefaultLocale ? null : $locale,
        );

        $this->gdOFactorRepository->add($gdOFactor);

        return new RegisterGdOFactorResponse(
            gdOFactorId: $gdOFactor->id(),
            name: $gdOFactor->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterGdOFactorRequest;
    }
}
