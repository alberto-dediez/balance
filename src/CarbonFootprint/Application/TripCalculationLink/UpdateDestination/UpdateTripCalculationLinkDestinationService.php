<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination;

use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Place\CountryRegionCode;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateTripCalculationLinkDestinationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationLinkAuthorizer $authorizer,
        private CountryRepository $countryRepository,
        private TripDestinationCandidates $tripDestinationCandidates,
        private PlaceFinder $placeFinder,
        private CountryRegionCode $countryRegionCode,
    ) {}

    public function __invoke(UpdateTripCalculationLinkDestinationRequest $request, User $user): UpdateTripCalculationLinkDestinationResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->find($request->tripCalculationLinkId);

        if (null === $tripCalculationLink) {
            throw new \InvalidArgumentException('TripCalculationLink not found');
        }

        $this->authorizer->denyUnlessCanDoThat($tripCalculationLink, Action::Edit, $user);

        if (TripDestinationMode::Countries === $request->mode) {
            $this->assertCountriesHaveIso($request);
        }

        $regionLabel = null;
        $regionBounds = null;

        if (TripDestinationMode::Region === $request->mode) {
            [$regionLabel, $regionBounds] = $this->region((string) $request->regionReference);
        }

        if (TripDestinationMode::SelectedProperties === $request->mode) {
            $this->assertPropertiesAreCandidates($request->propertyIds, $tripCalculationLink->accountId());
        }

        $tripCalculationLink->updateDestination(
            mode: $request->mode,
            placeKind: $request->placeKind,
            countryIds: $request->countryIds,
            propertyIds: $request->propertyIds,
            regionReference: $request->regionReference,
            regionLabel: $regionLabel,
            regionBounds: $regionBounds,
        );

        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return new UpdateTripCalculationLinkDestinationResponse($tripCalculationLink);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateTripCalculationLinkDestinationRequest;
    }

    private function assertCountriesHaveIso(UpdateTripCalculationLinkDestinationRequest $request): void
    {
        foreach ($request->countryIds as $countryId) {
            $iso = $this->countryRepository->find($countryId)?->iso();

            if (null === $iso || null === $this->countryRegionCode->alpha2($iso)) {
                throw new \InvalidArgumentException('El país elegido no tiene código ISO.');
            }
        }
    }

    /** @return array{0: string, 1: GeoBounds} */
    private function region(string $regionReference): array
    {
        if ('' === trim($regionReference)) {
            throw new \InvalidArgumentException('Debes elegir una región.');
        }

        $region = $this->placeFinder->resolve($regionReference, LocaleGetter::locale());

        if (null === $region?->viewport) {
            throw new \InvalidArgumentException('No se ha podido obtener la zona de la región elegida.');
        }

        return [$region->name ?? $region->label, $region->viewport];
    }

    /** @param list<PropertyId> $propertyIds */
    private function assertPropertiesAreCandidates(array $propertyIds, ?AccountId $accountId): void
    {
        $candidateIds = null !== $accountId
            ? array_map(
                static fn(Property $property): string => (string) $property->id(),
                $this->tripDestinationCandidates->candidatesFor($accountId),
            )
            : [];

        foreach ($propertyIds as $propertyId) {
            if (!in_array((string) $propertyId, $candidateIds, true)) {
                throw new \InvalidArgumentException('La propiedad elegida no está disponible para esta calculadora.');
            }
        }
    }
}
