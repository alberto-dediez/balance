<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Update;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateGdOFactorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private GdOFactorRepository $gdOFactorRepository,
        private GdOFactorAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateGdOFactorRequest $request, User $user): UpdateGdOFactorResponse
    {
        $gdOFactor = $this->gdOFactorRepository->find($request->gdOFactorId);

        if (null == $gdOFactor) {
            throw new \InvalidArgumentException('GdOFactor not found');
        }

        $this->authorizer->denyUnlessCanDoThat($gdOFactor, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $gdOFactor->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $gdOFactor->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(Unit::KWh),
                $request->emissionFactors,
            ),
        );

        $this->gdOFactorRepository->add($gdOFactor);

        return new UpdateGdOFactorResponse(
            gdOFactorId: $gdOFactor->id(),
            name: $gdOFactor->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateGdOFactorRequest;
    }
}
