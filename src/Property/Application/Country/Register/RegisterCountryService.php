<?php

namespace App\Property\Application\Country\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;

class RegisterCountryService
{
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
}