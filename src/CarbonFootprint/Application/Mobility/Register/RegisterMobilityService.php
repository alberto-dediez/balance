<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Register;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMobilityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MobilityRepository $mobilityRepository,
        private MobilityAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMobilityRequest $request, User $user): RegisterMobilityResponse
    {
        $id = MobilityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Mobility(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $mobility = new Mobility($id);
        $mobility->register(
            $request->name,
            $request->unit,
            $request->travelMode,
            $isDefaultLocale ? null : $locale,
        );

        $this->mobilityRepository->add($mobility);

        return new RegisterMobilityResponse(
            mobilityId: $mobility->id(),
            name: $mobility->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMobilityRequest;
    }
}
