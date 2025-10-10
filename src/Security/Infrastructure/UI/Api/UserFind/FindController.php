<?php

namespace App\Security\Infrastructure\UI\Api\UserFind;

use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use App\Security\Domain\Model\User\Role;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Security\Infrastructure\UI\Api\User\UserResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/find', name: 'api_users_find', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(
    summary: "Find a user by username",
    description: "[Only for admins] Find users by username",
    tags: ['Security']
)]
#[OA\Response(
    response: 200,
    description: 'Returns info about a registered user',
    content: new Model(type: UserResponse::class)
)]
class FindController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        FindRequest $request,
        UserRepository $repository
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        $user = $repository->findByUsername($request->username);

        return $this->json(new UserResponse(
            accountId: (string) $user->accountId(),
            username: $user->username(),
            userId: (string) $user->id()
        ));
    }
}
