<?php

namespace App\Property\Application\Country\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;

class ListCountriesService
{
    public function __construct(private readonly CountryRepository $repository, private readonly CountryAuthorizer $authorizer)
    {

    }

    public function __invoke(ListCountriesRequest $request, User $user): ListCountriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Country(
            id: CountryId::generate(),
            iso: $request->iso
        ), Action::List, $user);

        $countries = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCountriesResponse($countries, $totalResults);
    }
}