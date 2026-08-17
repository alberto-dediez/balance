<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api;

use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me', name: 'api_users_me', methods: ['GET'])]
#[Areas(['default', 'admin'])]
#[OA\Get(description: 'Return information about logged user', summary: 'Get info about me', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns info about user',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'username', type: 'string', example: 'user@example.org'),
            new OA\Property(property: 'account', type: 'string', example: 'My Company'),
        ],
    ),
)]
class UsersMeController extends AbstractController
{
    public function __invoke(
        LoggedAccount $loggedAccount,
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $username = $user->username();
        $account = $loggedAccount();

        return new JsonResponse(['username' => $username, 'account' => (string) $account]);
    }
}
