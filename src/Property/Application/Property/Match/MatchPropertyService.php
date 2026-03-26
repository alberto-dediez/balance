<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Match;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyUnmatched;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;

class MatchPropertyService
{
    public const MIN_PERCENT_MATCH = 75;

    public function __construct(private readonly PropertyRepository $propertyRepository, private readonly PropertyAuthorizer $authorizer) {}

    public function __invoke(MatchPropertyRequest $request, User $user): MatchPropertyResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
        ), Action::Admin, $user);

        $propertyIdFound = $this->findMatch($request);

        if (null == $propertyIdFound) {
            DomainEventPublisher::instance()->publish(new PropertyUnmatched(
                name: $request->name,
                info: $request->info,
                externalId: $request->externalId,
            ));

            return new MatchPropertyResponse($propertyIdFound);
        }

        $property = $this->propertyRepository->find($propertyIdFound);

        if ($property instanceof Property) {
            $property->match($request->name, $request->info, $request->externalId);
            $this->propertyRepository->add($property);
        }

        return new MatchPropertyResponse(
            propertyId: $propertyIdFound,
        );
    }

    private function findMatch(MatchPropertyRequest $request): ?PropertyId
    {
        $propertyIdFound = $this->findByMainExternalId($request);

        if ($propertyIdFound instanceof PropertyId) {
            return $propertyIdFound;
        }

        /** @var array<string, array{propertyId: PropertyId, matchPercent: float}> $possibleMatchs */
        $possibleMatchs = [];

        $propertyIdFound = $this->findByExternalId($request);

        if ($propertyIdFound instanceof PropertyId) {
            if (!isset($possibleMatchs[(string) $propertyIdFound])) {
                $possibleMatchs[(string) $propertyIdFound] = [
                    'propertyId' => $propertyIdFound,
                    'matchPercent' => 0.0,
                ];
            }

            $possibleMatchs[(string) $propertyIdFound]['matchPercent'] += 90.0;
        }

        $propertyIdFound = $this->findByName($request);

        if ($propertyIdFound instanceof PropertyId) {
            if (!isset($possibleMatchs[(string) $propertyIdFound])) {
                $possibleMatchs[(string) $propertyIdFound] = [
                    'propertyId' => $propertyIdFound,
                    'matchPercent' => 0.0,
                ];
            }

            $possibleMatchs[(string) $propertyIdFound]['matchPercent'] += 50.0;
        }

        $propertyIdFound = $this->findByGeoPosition($request);

        if ($propertyIdFound instanceof PropertyId) {
            if (!isset($possibleMatchs[(string) $propertyIdFound])) {
                $possibleMatchs[(string) $propertyIdFound] = [
                    'propertyId' => $propertyIdFound,
                    'matchPercent' => 0.0,
                ];
            }

            $possibleMatchs[(string) $propertyIdFound]['matchPercent'] += 50.0;
        }

        if (0 === count($possibleMatchs)) {
            return null;
        }

        return $this->getBestMatch($possibleMatchs);
    }

    /**
     * @param array<string, array{propertyId: PropertyId, matchPercent: float}> $possibleMatchs
     */
    private function getBestMatch(array $possibleMatchs): ?PropertyId
    {
        $bestMatch = null;
        $bestMatchPercent = 0.0;

        foreach ($possibleMatchs as $match) {
            if (null === $bestMatch || $match['matchPercent'] > $bestMatchPercent) {
                $bestMatch = $match['propertyId'];
                $bestMatchPercent = $match['matchPercent'];
            }
        }

        return $bestMatch;
    }

    private function findByMainExternalId(MatchPropertyRequest $request): ?PropertyId
    {
        if ($request->externalId->isEmpty()) {
            return null;
        }

        $property = $this->propertyRepository->findBySourceAndExternalId($request->source, $request->externalId);

        return $property instanceof Property ? $property->id() : null;
    }

    private function findByExternalId(MatchPropertyRequest $request): ?PropertyId
    {
        if ($request->externalId->isEmpty()) {
            return null;
        }

        $property = $this->propertyRepository->findByExternalId($request->externalId);

        if (null == $property) {
            return null;
        }

        if (!$property->externalId()->isCombinable($request->externalId)) {
            return null;
        }

        return $property->id();
    }

    private function findByGeoPosition(MatchPropertyRequest $request): ?PropertyId
    {
        if (null == $request->info->latitude() || null == $request->info->longitude()) {
            return null;
        }

        $property = $this->propertyRepository->findByGeoPosition($request->info->latitude(), $request->info->longitude());

        if (null == $property) {
            return null;
        }

        if (!$property->externalId()->isCombinable($request->externalId)) {
            return null;
        }

        return $property->id();
    }

    private function findByName(MatchPropertyRequest $request): ?PropertyId
    {
        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                name: $request->name,
                countryId: $request->info->countryId(),
            ),
        );

        if (0 === count($properties)) {
            $properties = $this->propertyRepository->findByFilter(
                new PropertyFilter(
                    destinationId: $request->info->destinationId(),
                    countryId: $request->info->countryId(),
                    limit: 10000,
                ),
            );
        }

        if (0 === count($properties)) {
            $properties = $this->propertyRepository->findByFilter(
                new PropertyFilter(
                    countryId: $request->info->countryId(),
                    limit: 100000,
                ),
            );
        }

        $possibleMatch = null;
        $possibleMatchPercent = 0.0;
        $matchPercent = 0.0;

        foreach ($properties as $property) {
            if (! $property->externalId()->isCombinable($request->externalId)) {
                continue;
            }
            if (null === $property->name()) {
                continue;
            }

            similar_text(strtolower($request->name), strtolower($property->name()), $matchPercent);

            if ($matchPercent < self::MIN_PERCENT_MATCH) {
                continue;
            }

            if (null !== $property->info()->zone() && null !== $request->info->zone() && strtolower($property->info()->zone()) === strtolower($request->info->zone())) {
                $matchPercent += 50;
            }

            if ($request->info->destinationId() instanceof DestinationId && $property->info()->destinationId()->equals($request->info->destinationId())) {
                $matchPercent += 100;
            }

            if (null == $possibleMatch || $matchPercent > $possibleMatchPercent) {
                $possibleMatch = $property->id();
            }
        }

        return $possibleMatch;
    }
}
