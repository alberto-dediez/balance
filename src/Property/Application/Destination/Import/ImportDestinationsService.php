<?php

namespace App\Property\Application\Destination\Import;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Application\Destination\Import\DestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use App\Property\Application\Destination\Register\RegisterDestinationRequest;
use App\Property\Application\Destination\Register\RegisterDestinationService;

class ImportDestinationsService
{
    const MIN_PERCENT_MATCH = 75;

    public function __construct(
        private readonly DestinationRepository $repository, 
        private readonly RegisterDestinationService $registerDestination, 
        private readonly UpdateDestinationService $updateDestination, 
        private readonly DestinationAuthorizer $authorizer
    ) {

    }

    public function __invoke(ImportDestinationsRequest $request, User $user): ImportDestinationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Destination(
            id: DestinationId::generate()
        ), Action::Admin, $user);

        $destinationsMatch = $this->matchDestinations($request->destinations, $user);

        $totalResults = array_sum(
            array_map(fn(DestinationResponse $response) => $response->destinationId instanceof DestinationId ? 1 : 0, $destinationsMatch)
        );

        return new ImportDestinationsResponse($destinationsMatch, $totalResults);
    }

    private function matchDestinations(array $destinationRequests, User $user): array
    {
        if (count($destinationRequests) === 0) {
            return [];
        }

        $result = [];

        foreach ($destinationRequests as $request) {
            $destinationMatch = $this->repository->findBySourceAndExternalId(
                source: $request->source,
                externalId: $request->externalId
            );

            if (null !== $request->countryId && null == $destinationMatch) {
                $destinationMatch = $this->findByNameAndCountry($request);

                if ($destinationMatch instanceof Destination) {
                    $this->updateDestination->__invoke(new UpdateDestinationRequest(
                        destinationId: $destinationMatch->id(),
                        name: $destinationMatch->name(),
                        countryId: $destinationMatch->countryId(),
                        externalId: $destinationMatch->externalId()->combine(
                            $request->externalId
                        )
                    ), $user);
                }

                if (null == $destinationMatch && $request->createIfNotExists) {
                    $registerResponse = $this->registerDestination->__invoke(new RegisterDestinationRequest(
                        name: $request->name,
                        countryId: $request->countryId,
                        externalId: $request->externalId,
                    ), $user);

                    $destinationMatch = new Destination($registerResponse->destinationId);
                }
            }

            $result[] = new DestinationResponse(
                name: $request->name,
                countryId: $request->countryId,
                externalId: $request->externalId,
                createIfNotExists: $request->createIfNotExists,
                destinationId: $destinationMatch?->id()
            );
        }

        return $result;
    }

    private function findByNameAndCountry(DestinationRequest $request): ?Destination
    {
        $destinationMatch = $this->repository->findByNameAndCountry($request->name, $request->countryId);

        if ($destinationMatch instanceof Destination) {
            return $destinationMatch;
        }

        $destinations = $this->repository->findByFilter(
            new DestinationFilter(
                countryId: $request->countryId,
                limit: 10000
            )
        );

        $possibleMatch = null;
        $possibleMatchPercent = 0.0;
        $matchPercent = 0.0;

        foreach ($destinations as $destination) {
            if (! $destination->externalId()->isCombinable($request->externalId)) {
                continue;
            }

            similar_text($request->name, (string) $destination->name(), $matchPercent);

            if ($matchPercent < self::MIN_PERCENT_MATCH) {
                continue;
            }

            if (null == $possibleMatch || $matchPercent > $possibleMatchPercent) {
                $possibleMatch = $destination;
            }
        }

        return $possibleMatch;
    }
}