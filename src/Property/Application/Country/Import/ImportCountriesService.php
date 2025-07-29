<?php

namespace App\Property\Application\Country\Import;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Application\Country\Register\RegisterCountryRequest;
use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Application\Country\Update\UpdateCountryRequest;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Domain\Model\Country\CountryRepository;

class ImportCountriesService
{
    public function __construct(
        private CountryRepository $repository, 
        private RegisterCountryService $registerCountry, 
        private UpdateCountryService $updateCountry, 
        private CountryAuthorizer $authorizer
    ) {

    }

    public function __invoke(ImportCountriesRequest $request, User $user): ImportCountriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Country(
            id: CountryId::generate()
        ), Action::Admin, $user);

        $countriesMatch = $this->matchCountries($request->countries, $user);

        $totalResults = array_sum(
            array_map(function (CountryResponse $response) {
                return null !== $response->countryId ? 1 : 0;
            }, $countriesMatch)
        );

        return new ImportCountriesResponse($countriesMatch, $totalResults);
    }

    private function matchCountries(array $countryRequests, User $user): array
    {
        if (count($countryRequests) == 0) {
            return [];
        }

        $result = [];

        foreach ($countryRequests as $request) {
            $countryMatch = $this->repository->findBySourceAndExternalId(
                source: $request->source,
                externalId: $request->externalId
            );

            if (null == $countryMatch && null != $request->iso && !empty($request->iso)) {
                $countryMatch = $this->repository->findByIso($request->iso);
            }

            if (null == $countryMatch) {
                $countryMatch = $this->repository->findByName($request->name);
            }

            if (null !== $countryMatch) {
                $this->updateCountry->__invoke(new UpdateCountryRequest(
                    $countryMatch->id(),
                    $countryMatch->name(),
                    $countryMatch->iso(),
                    $countryMatch->externalId()->combine(
                        $request->externalId
                    )
                ), $user);
            }

            if (null == $countryMatch && $request->createIfNotExists) {
                $registerResponse = $this->registerCountry->__invoke(new RegisterCountryRequest(
                    name: $request->name,
                    iso: $request->iso,
                    externalId: $request->externalId,
                ), $user);

                $countryMatch = new Country($registerResponse->countryId);
            }

            $result[] = new CountryResponse(
                name: $request->name,
                iso: $request->iso,
                externalId: $request->externalId,
                createIfNotExists: $request->createIfNotExists,
                countryId: $countryMatch?->id()
            );
        }

        return $result;
    }
}