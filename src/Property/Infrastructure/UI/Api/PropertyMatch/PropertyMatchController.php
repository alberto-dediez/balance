<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyMatch;

use App\Property\Application\Property\Match\MatchPropertyRequest;
use App\Property\Application\Property\Match\MatchPropertyService;
use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Application\Property\Update\UpdatePropertyService;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties/match', name: 'api_properties_match', methods: ['POST'])]
#[Areas(['admin'])]
#[OA\Post(description: 'Match an external property with an existing one in the system', summary: 'Match Property', tags: ['Property'])]
#[OA\Response(
    response: 200,
    description: 'Returns the matched property id, or null if no match was found',
    content: new Model(type: PropertyMatchResponse::class),
)]
class PropertyMatchController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        PropertyMatchRequest $request,
        MatchPropertyService $matchProperty,
        UpdatePropertyService $updateProperty,
        PropertyRepository $propertyRepository,
        CountryRepository $countryRepository,
        DestinationRepository $destinationRepository,
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $source = ExternalSource::from($request->source);

        $externalId = null !== $request->externalId
            ? ExternalId::fromSourceAndId($source, $request->externalId)
            : new ExternalId();

        $countryId = $this->resolveCountryId($request, $source, $countryRepository);
        $destinationId = $this->resolveDestinationId($request, $source, $countryId, $destinationRepository);

        $info = new PropertyInfo(
            latitude: $request->latitude,
            longitude: $request->longitude,
            address: $request->address,
            postalCode: $request->postalCode,
            zone: $request->zone,
            countryId: $countryId,
            destinationId: $destinationId,
            rooms: $request->rooms,
        );

        $response = $matchProperty(
            new MatchPropertyRequest(
                name: $request->name,
                info: $info,
                externalId: $externalId,
                source: $source,
            ),
            $user,
        );

        $propertyId = $response->propertyId;
        $matchedAccountId = null;

        if (null !== $propertyId) {
            $property = $propertyRepository->find($propertyId);

            if (null !== $property) {
                $matchedAccountId = $property->accountId();

                if (null !== $request->accountId && null === $matchedAccountId) {
                    $accountId = new AccountId($request->accountId);
                    $updateProperty(
                        new UpdatePropertyRequest(
                            propertyId: $propertyId,
                            name: $property->name(),
                            accountId: $accountId,
                            info: $property->info(),
                            externalId: $property->externalId(),
                        ),
                        $user,
                    );
                    $matchedAccountId = $accountId;
                }
            }
        }

        return $this->json(
            new PropertyMatchResponse(
                propertyId: null !== $propertyId ? (string) $propertyId : null,
                accountId: null !== $matchedAccountId ? (string) $matchedAccountId : null,
            ),
        );
    }

    private function resolveCountryId(PropertyMatchRequest $request, ExternalSource $source, CountryRepository $countryRepository): ?CountryId
    {
        if (null === $request->country) {
            return null;
        }

        if (!empty($request->country->externalId)) {
            $country = $countryRepository->findBySourceAndExternalId(
                $source,
                ExternalId::fromSourceAndId($source, $request->country->externalId),
            );
            if (null !== $country) {
                return $country->id();
            }
        }

        if (!empty($request->country->iso)) {
            $country = $countryRepository->findByIso($request->country->iso);
            if (null !== $country) {
                return $country->id();
            }
        }

        return null;
    }

    private function resolveDestinationId(PropertyMatchRequest $request, ExternalSource $source, ?CountryId $countryId, DestinationRepository $destinationRepository): ?DestinationId
    {
        if (null === $request->destination) {
            return null;
        }

        if (!empty($request->destination->externalId)) {
            $destination = $destinationRepository->findBySourceAndExternalId(
                $source,
                ExternalId::fromSourceAndId($source, $request->destination->externalId),
            );
            if (null !== $destination) {
                return $destination->id();
            }
        }

        if (!empty($request->destination->name) && null !== $countryId) {
            $destination = $destinationRepository->findByNameAndCountry($request->destination->name, $countryId);
            if (null !== $destination) {
                return $destination->id();
            }
        }

        return null;
    }
}
