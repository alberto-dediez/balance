<?php

namespace App\Property\Application\Country\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Country\Country;
use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterCountryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private CountryRepository $countryRepository, private CountryAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterCountryRequest $request, User $user): RegisterCountryResponse
    {
        $id = CountryId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Country(
            id: CountryId::generate(),
            iso: $request->iso
        ), Action::Register, $user);

        $country = new Country($id);
        $country->register($request->name, $request->iso, $request->externalId);

        $this->countryRepository->add($country);

        return new RegisterCountryResponse($country->id(), $country->name(), $country->iso());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCountryRequest;
    }
}