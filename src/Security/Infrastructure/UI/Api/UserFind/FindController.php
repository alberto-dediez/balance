<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserFind;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\UI\Api\User\UserResponse;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/find', name: 'api_users_find', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: '[Only for admins] Find users by username', summary: 'Find a user by username', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns info about a registered user',
    content: new Model(type: UserResponse::class),
)]
class FindController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        FindRequest $request,
        UserRepository $repository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        $user = $repository->findByUsername($request->username);

        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        return $this->json(new UserResponse(
            userId: (string) $user->id(),
            username: $user->username(),
            accountId: (string) $user->accountId(),
        ));
    }
}
