<?php

namespace App\Security\Infrastructure\UI\Api\User;

use OpenApi\Attributes as OA;
use App\Security\Domain\Model\User\Role;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Shared\Domain\Uuid\UuidGenerator;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users', name: 'api_users_register', methods: ['POST'])]
#[OA\Post( 
    summary: "Register a new user",
    description: "[Only for admins] Register a new user",
    tags: ['Security']
)]
#[OA\Response(
    response: 200,
    description: 'Returns info about new registered user',
    content: new Model(type: UserResponse::class)
)]
class RegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        UserRequest $request,
        RegisterUserService $register,
        LoggedUser $loggedUser
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        $accountId = (null === $request->accountId || empty($request->accountId))
            ? null
            : new AccountId($request->accountId)
        ;

        $response = $register(new RegisterUserRequest(
            username: $request->username,
            password: UuidGenerator::v4(),
            accountId: $accountId
        ), $loggedUser()); 

        return $this->json(new UserResponse(
            accountId: (string) $response->accountId,
            username: $response->username,
            userId: (string) $response->userId
        ));
    }
}
