<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\ApiToken;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenRequest;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/profile/api-tokens/{apiTokenId}/revoke', name: 'profile_api_tokens_revoke', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
#[CsrfProtected('revoke')]
class RevokeController extends AbstractController
{
    public function __invoke(
        string $apiTokenId,
        RevokeApiTokenService $revokeApiToken,
        ApiTokenPolicy $policy,
        LoggedUser $loggedUser,
    ): Response {
        $user = $loggedUser();

        if (!$policy->userCanHaveTokens($user)) {
            throw $this->createAccessDeniedException();
        }

        try {
            $revokeApiToken(new RevokeApiTokenRequest(new ApiTokenId($apiTokenId)), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $this->addFlash('warning', new TranslatableMessage('Token revocado'));

        return $this->redirectToRoute('profile_api_tokens');
    }
}
