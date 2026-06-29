<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Update;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateOvernightStayService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OvernightStayRepository $overnightStayRepository,
        private OvernightStayAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateOvernightStayRequest $request, User $user): UpdateOvernightStayResponse
    {
        $overnightStay = $this->overnightStayRepository->find($request->overnightStayId);

        if (null == $overnightStay) {
            throw new \InvalidArgumentException('OvernightStay not found');
        }

        $this->authorizer->denyUnlessCanDoThat($overnightStay, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $overnightStay->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $overnightStay->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data) => $data->toEmissionFactor(),
                $request->emissionFactors,
            ),
        );

        $this->overnightStayRepository->add($overnightStay);

        return new UpdateOvernightStayResponse(
            overnightStayId: $overnightStay->id(),
            name: $overnightStay->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateOvernightStayRequest;
    }
}
