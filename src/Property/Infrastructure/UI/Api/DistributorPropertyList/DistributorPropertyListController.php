<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\DistributorPropertyList;

use App\Property\Application\Property\ListDistributorProperties\ListDistributorPropertiesRequest;
use App\Property\Application\Property\ListDistributorProperties\ListDistributorPropertiesService;
use App\Property\Domain\Model\DistributorAssignment\AccountIsNotDistributorException;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponseGenerator;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/distributors/{distributorId}/properties', name: 'api_distributors_properties_list', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: 'Return the own and distributed properties of a distributor account', summary: 'List Distributor Properties', tags: ['Property'])]
#[OA\Response(
    response: 200,
    description: 'Returns list of properties',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: PropertyResponse::class)),
    ),
)]
#[OA\Response(response: 404, description: 'The account is not an active distributor')]
class DistributorPropertyListController extends AbstractController
{
    public function __invoke(
        string $distributorId,
        ListDistributorPropertiesService $listDistributorProperties,
        PropertyResponseGenerator $propertyGenerator,
        LoggedUser $loggedUser,
    ): JsonResponse {
        try {
            $response = $listDistributorProperties(
                new ListDistributorPropertiesRequest(new AccountId($distributorId)),
                $loggedUser(),
            );
        } catch (AccountIsNotDistributorException) {
            throw $this->createNotFoundException();
        }

        $items = [];
        foreach ($response->properties as $property) {
            $items[] = $propertyGenerator->generate($property);
        }

        return $this->json($items);
    }
}
