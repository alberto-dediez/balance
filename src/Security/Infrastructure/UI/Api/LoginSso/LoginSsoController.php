<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\LoginSso;

use App\Security\Infrastructure\SSO\SSOClient;
use App\Security\Infrastructure\SSO\SSOJwtDecoderInterface;
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
#[OA\Post(description: 'Exchange an SSO token for an API JWT token', summary: 'SSO login', tags: ['Security'])]
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
        SSOClient $ssoClient,
        SSOJwtDecoderInterface $jwtDecoder,
        UserProvider $userProvider,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        if (!$ssoClient->checkToken($request->token)) {
            return $this->json(['error' => 'Invalid SSO token.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $jwtDecoder->decode($request->token);
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid SSO token.'], Response::HTTP_UNAUTHORIZED);
        }

        $emailInToken = $payload['email'] ?? null;

        if ($emailInToken !== $request->username) {
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
