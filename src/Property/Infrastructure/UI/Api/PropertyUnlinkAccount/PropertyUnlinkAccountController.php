<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyUnlinkAccount;

use App\Property\Application\Property\ReleaseAccount\ReleasePropertyAccountRequest;
use App\Property\Application\Property\ReleaseAccount\ReleasePropertyAccountService;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponseGenerator;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties/{propertyId}/account', name: 'api_properties_unlink_account', methods: ['DELETE'])]
#[Areas(['admin'])]
#[OA\Delete(description: 'Unlink a property from its account', summary: 'Unlink Property Account', tags: ['Property'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of the updated property',
    content: new Model(type: PropertyResponse::class),
)]
class PropertyUnlinkAccountController extends AbstractController
{
    public function __invoke(
        string $propertyId,
        PropertyRepository $propertyRepository,
        ReleasePropertyAccountService $releasePropertyAccount,
        PropertyResponseGenerator $propertyGenerator,
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $property = $propertyRepository->find(new PropertyId($propertyId));

        if (!$property instanceof Property) {
            throw $this->createNotFoundException();
        }

        $response = $releasePropertyAccount(
            new ReleasePropertyAccountRequest(propertyId: $property->id()),
            $user,
        );

        $property = $propertyRepository->find($response->propertyId);
        assert($property instanceof Property);

        return $this->json($propertyGenerator->generate($property));
    }
}
