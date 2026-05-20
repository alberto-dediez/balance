<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyMatch;

use App\Property\Application\Property\Match\MatchPropertyRequest;
use App\Property\Application\Property\Match\MatchPropertyService;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyInfo;
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
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $source = ExternalSource::from($request->source);

        $externalId = null !== $request->externalId
            ? ExternalId::fromSourceAndId($source, $request->externalId)
            : new ExternalId();

        $info = new PropertyInfo(
            latitude: $request->latitude,
            longitude: $request->longitude,
            address: $request->address,
            postalCode: $request->postalCode,
            zone: $request->zone,
            countryId: null !== $request->countryId ? new CountryId($request->countryId) : null,
            destinationId: null !== $request->destinationId ? new DestinationId($request->destinationId) : null,
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

        return $this->json(
            new PropertyMatchResponse(
                propertyId: null !== $response->propertyId ? (string) $response->propertyId : null,
            ),
        );
    }
}
