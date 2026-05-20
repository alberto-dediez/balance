<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserUngrantRole;

use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{userId}/ungrant-role', name: 'api_users_ungrant_role', methods: ['PUT'])]
#[Areas(['admin'])]
#[OA\Put(description: 'Ungrant a role from a user', summary: 'Ungrant role from user', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns updated user data',
    content: new Model(type: UserUngrantRoleResponse::class),
)]
class UserUngrantRoleController extends AbstractController
{
    public function __invoke(
        string $userId,
        #[MapRequestPayload()]
        UserUngrantRoleRequest $request,
        UngrantRoleService $ungrantRole,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $response = $ungrantRole(
            new UngrantRoleRequest(
                userId: new UserId($userId),
                role: Role::from($request->role),
            ),
            $loggedUser(),
        );

        return $this->json(new UserUngrantRoleResponse(
            userId: (string) $response->userId,
            username: $response->username,
            roles: array_map(static fn(Role $role) => $role->value, $response->roles),
        ));
    }
}
