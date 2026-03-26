<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Import;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Application\Country\Register\RegisterCountryRequest;
use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Application\Country\Update\UpdateCountryRequest;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ImportCountriesService
{
    public function __construct(
        private readonly CountryRepository $repository,
        private readonly RegisterCountryService $registerCountry,
        private readonly UpdateCountryService $updateCountry,
        private readonly CountryAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportCountriesRequest $request, User $user): ImportCountriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Country(
            id: CountryId::generate(),
        ), Action::Admin, $user);

        $countriesMatch = $this->matchCountries($request->countries, $user);

        $totalResults = array_sum(
            array_map(fn(CountryResponse $response) => $response->countryId instanceof CountryId ? 1 : 0, $countriesMatch),
        );

        return new ImportCountriesResponse($countriesMatch, $totalResults);
    }

    /**
     * @param CountryRequest[] $countryRequests
     *
     * @return CountryResponse[]
     */
    private function matchCountries(array $countryRequests, User $user): array
    {
        if (0 === count($countryRequests)) {
            return [];
        }

        $result = [];

        foreach ($countryRequests as $request) {
            $countryMatch = $this->repository->findBySourceAndExternalId(
                source: $request->source,
                externalId: $request->externalId,
            );

            if (null == $countryMatch && null != $request->iso && !empty($request->iso)) {
                $countryMatch = $this->repository->findByIso($request->iso);
            }

            if (null == $countryMatch) {
                $countryMatch = $this->repository->findByName($request->name);
            }

            if ($countryMatch instanceof Country) {
                $this->updateCountry->__invoke(new UpdateCountryRequest(
                    $countryMatch->id(),
                    $countryMatch->name(),
                    $countryMatch->iso(),
                    $countryMatch->externalId()->combine(
                        $request->externalId,
                    ),
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
                countryId: $countryMatch?->id(),
            );
        }

        return $result;
    }
}
