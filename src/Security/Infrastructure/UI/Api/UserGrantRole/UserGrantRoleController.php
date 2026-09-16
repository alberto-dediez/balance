<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserGrantRole;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
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

#[Route('/users/{userId}/grant-role', name: 'api_users_grant_role', methods: ['PUT'])]
#[Areas(['admin'])]
#[OA\Put(description: 'Grant a role to a user', summary: 'Grant role to user', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns updated user data',
    content: new Model(type: UserGrantRoleResponse::class),
)]
class UserGrantRoleController extends AbstractController
{
    public function __invoke(
        string $userId,
        #[MapRequestPayload()]
        UserGrantRoleRequest $request,
        GrantRoleService $grantRole,
        LoggedUser $loggedUser,
    ): JsonResponse {
        try {
            $response = $grantRole(
                new GrantRoleRequest(
                    userId: new UserId($userId),
                    role: Role::from($request->role),
                ),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->json(new UserGrantRoleResponse(
            userId: (string) $response->userId,
            username: $response->username,
            roles: array_map(static fn(Role $role) => $role->value, $response->roles),
        ));
    }
}
