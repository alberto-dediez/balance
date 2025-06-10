<?php

namespace App\Security\Infrastructure\UI\Api;

use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Twig\AccountExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/me', name: 'api_users_me', methods: ['GET'])]
#[OA\Get( 
    summary: "Get info about me",
    description: "Return information about logged user",
    tags: ['Security']
)]
#[OA\Response(
    response: 200,
    description: 'Returns info about user',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'username', type: 'string', example: 'user@example.org'),
            new OA\Property(property: 'account', type: 'string', example: 'My Company'),
        ]
    )
)]
class UsersMeController extends AbstractController
{
    public function __invoke(
        AccountExtension $accountExtension
    ): JsonResponse
    {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $username = $user->username();
        $account = null !== $user->accountId() ? $accountExtension->accountName($user->accountId()) : null;

        return new JsonResponse(['username' => $username, 'account' => (string) $account]);
    }
}
