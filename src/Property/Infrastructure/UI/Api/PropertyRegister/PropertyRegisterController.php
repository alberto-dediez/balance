<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponseGenerator;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties', name: 'api_properties_register', methods: ['POST'])]
#[Areas(['admin'])]
#[OA\Post(description: 'Register a new property assigned to an account', summary: 'Register Property', tags: ['Property'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of the registered property',
    content: new Model(type: PropertyResponse::class),
)]
class PropertyRegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        PropertyRegisterRequest $request,
        RegisterPropertyService $registerProperty,
        PropertyRepository $propertyRepository,
        PropertyResponseGenerator $propertyGenerator,
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $info = new PropertyInfo(
            propertyType: null !== $request->propertyType ? PropertyType::from($request->propertyType) : null,
            categoryId: null !== $request->categoryId ? new CategoryId($request->categoryId) : null,
            brandId: null !== $request->brandId ? new BrandId($request->brandId) : null,
            address: $request->address,
            postalCode: $request->postalCode,
            longitude: $request->longitude,
            latitude: $request->latitude,
            rooms: $request->rooms,
            zone: $request->zone,
            web: $request->web,
            destinationId: null !== $request->destinationId ? new DestinationId($request->destinationId) : null,
            countryId: null !== $request->countryId ? new CountryId($request->countryId) : null,
        );

        $externalId = new ExternalId(
            hotelbeds: $request->hotelbeds,
            restel: $request->restel,
            bioscore: $request->bioscore,
        );

        $response = $registerProperty(
            new RegisterPropertyRequest(
                name: $request->name,
                accountId: new AccountId($request->accountId),
                info: $info,
                externalId: $externalId,
            ),
            $user,
        );

        $property = $propertyRepository->find($response->propertyId);
        assert($property instanceof Property);

        return $this->json($propertyGenerator->generate($property));
    }
}
