<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Register;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterOvernightStayService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OvernightStayRepository $overnightStayRepository,
        private OvernightStayAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterOvernightStayRequest $request, User $user): RegisterOvernightStayResponse
    {
        $id = OvernightStayId::generate();

        $this->authorizer->denyUnlessCanDoThat(new OvernightStay(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $overnightStay = new OvernightStay($id);
        $overnightStay->register(
            $request->name,
            $isDefaultLocale ? null : $locale,
        );

        $this->overnightStayRepository->add($overnightStay);

        return new RegisterOvernightStayResponse(
            overnightStayId: $overnightStay->id(),
            name: $overnightStay->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterOvernightStayRequest;
    }
}
