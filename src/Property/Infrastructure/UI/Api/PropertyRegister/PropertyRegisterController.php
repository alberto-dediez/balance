<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
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

        $response = $registerProperty(
            new RegisterPropertyRequest(
                name: $request->name,
                accountId: new AccountId($request->accountId),
            ),
            $user,
        );

        $property = $propertyRepository->find($response->propertyId);
        assert($property instanceof Property);

        return $this->json($propertyGenerator->generate($property));
    }
}
