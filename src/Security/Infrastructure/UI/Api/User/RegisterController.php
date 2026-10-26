<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\User;

use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Uuid\UuidGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', name: 'api_users_register', methods: ['POST'])]
#[Areas(['admin'])]
#[OA\Post(description: '[Only for admins] Register a new user', summary: 'Register a new user', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns info about new registered user',
    content: new Model(type: UserResponse::class),
)]
#[OA\Response(
    response: 422,
    description: 'The username contains invalid characters',
)]
class RegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        UserRequest $request,
        RegisterUserService $register,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        $accountId = '' === $request->accountId || '0' === $request->accountId
            ? null
            : new AccountId($request->accountId)
        ;

        $response = $register(new RegisterUserRequest(
            username: $request->username,
            password: UuidGenerator::v4(),
            accountId: $accountId,
        ), $loggedUser());

        return $this->json(new UserResponse(
            userId: (string) $response->userId,
            username: $response->username,
            accountId: (string) $response->accountId,
        ));
    }
}
