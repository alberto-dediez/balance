<?php

namespace App\Property\Application\Country\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;

class UpdateCountryService
{
    public function __construct(private CountryRepository $repository, private CountryAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateCountryRequest $request, User $user): UpdateCountryResponse
    {
        $country = $this->repository->find($request->countryId);

        if (null == $country) {
            throw new InvalidArgumentException("Country not found");
        }

        $this->authorizer->denyUnlessCanDoThat($country, Action::Edit, $user);

        $country->update($request->name, $request->iso, $request->externalId);

        $this->repository->add($country);

        return new UpdateCountryResponse(
            countryId: $country->id(),
            name: $country->name(),
            iso: $country->iso()
        );
    }
}