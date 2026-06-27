<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Register;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteRepository $wasteRepository,
        private WasteAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterWasteRequest $request, User $user): RegisterWasteResponse
    {
        $id = WasteId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Waste(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $waste = new Waste($id);
        $waste->register(
            $request->name,
            $request->unit,
            $request->density,
            $isDefaultLocale ? null : $locale,
        );

        $this->wasteRepository->add($waste);

        return new RegisterWasteResponse(
            wasteId: $waste->id(),
            name: $waste->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterWasteRequest;
    }
}
