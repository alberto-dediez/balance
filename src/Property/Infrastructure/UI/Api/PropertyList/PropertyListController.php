<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyList;

use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponseGenerator;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentUser\CurrentUserContext;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties', name: 'api_properties_list', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: 'Return properties belonging to the authenticated user\'s account', summary: 'List Properties', tags: ['Property'])]
#[OA\Parameter(name: 'account_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by account (admin only)')]
#[OA\Response(
    response: 200,
    description: 'Returns list of properties',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: PropertyResponse::class)),
    ),
)]
class PropertyListController extends AbstractController
{
    public function __invoke(
        Request $request,
        ListPropertiesService $listProperties,
        PropertyResponseGenerator $propertyGenerator,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        CurrentUserContext $currentUserContext,
    ): JsonResponse {
        $user = $loggedUser();

        $rawAccountId = $request->query->get('account_id');
        $accountId = $currentUserContext->hasRole($user, Role::Admin) && null !== $rawAccountId
            ? new AccountId($rawAccountId)
            : $loggedAccount()?->id();

        $response = $listProperties(
            new ListPropertiesRequest(
                accountId: $accountId,
            ),
            $user,
        );

        $items = [];
        foreach ($response->properties as $property) {
            $items[] = $propertyGenerator->generate($property);
        }

        return $this->json($items);
    }
}
