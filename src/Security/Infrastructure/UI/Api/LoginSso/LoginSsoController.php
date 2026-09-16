<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\LoginSso;

use App\Security\Infrastructure\Sso\Bioscore\BioscoreSsoProvider;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Symfony\Security\UserProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Route('/login/sso', name: 'api_login_sso', methods: ['POST'])]
#[Areas(['mobile', 'admin'])]
#[OA\Post(description: 'Exchange a Bioscore SSO token for an API JWT token', summary: 'SSO login', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns a JWT token',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
        ],
    ),
)]
class LoginSsoController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        LoginSsoRequest $request,
        BioscoreSsoProvider $bioscore,
        UserProvider $userProvider,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        try {
            $identity = $bioscore->identityFromToken($request->token);
        } catch (SsoAuthenticationFailedException) {
            return $this->json(['error' => 'Invalid SSO token.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($identity->email !== mb_strtolower($request->username)) {
            return $this->json(['error' => 'Invalid SSO token.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $user = $userProvider->loadUserByIdentifier($request->username);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'token' => $jwtManager->create($user),
        ]);
    }
}
