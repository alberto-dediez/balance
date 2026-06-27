<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Update;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteRepository $wasteRepository,
        private WasteAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateWasteRequest $request, User $user): UpdateWasteResponse
    {
        $waste = $this->wasteRepository->find($request->wasteId);

        if (null == $waste) {
            throw new \InvalidArgumentException('Waste not found');
        }

        $this->authorizer->denyUnlessCanDoThat($waste, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $waste->update(
            name: $request->name,
            unit: $request->unit,
            density: $request->density,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $waste->unit();

        $waste->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor($unit),
                $request->emissionFactors,
            ),
        );

        $this->wasteRepository->add($waste);

        return new UpdateWasteResponse(
            wasteId: $waste->id(),
            name: $waste->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateWasteRequest;
    }
}
