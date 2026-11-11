<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\ApiToken;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\Register\RegisterApiTokenRequest;
use App\Security\Application\ApiToken\Register\RegisterApiTokenService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\ApiToken\ApiTokenExpirationInvalidException;
use App\Security\Domain\Model\ApiToken\ApiTokenLimitReachedException;
use App\Security\Domain\Model\ApiToken\ApiTokensNotAllowedException;
use App\Security\Infrastructure\Symfony\Form\RegisterApiTokenType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile/api-tokens/register', name: 'profile_api_tokens_register')]
#[IsGranted('ROLE_USER')]
class RegisterController extends AbstractController
{
    public function __invoke(
        RegisterApiTokenService $registerApiToken,
        ApiTokenPolicy $policy,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        if (!$policy->userCanHaveTokens($user)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RegisterApiTokenType::class, new RegisterApiTokenRequest());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $apiToken = $registerApiToken($form->getData(), $user);

                $response = $this->render('@security/api-tokens/created.html.twig', [
                    'apiToken' => $apiToken,
                ]);

                $response->headers->set('Cache-Control', 'no-store');

                return $response;
            } catch (UnauthorizedActionException|ApiTokensNotAllowedException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (ApiTokenLimitReachedException) {
                $form->get('name')->addError(new FormError($translator->trans('Has alcanzado el máximo de 10 tokens activos. Revoca alguno para crear otro')));
            } catch (ApiTokenExpirationInvalidException) {
                $form->get('expiresAt')->addError(new FormError($translator->trans('La fecha de caducidad debe ser posterior a hoy')));
            }
        }

        return $this->render('@security/api-tokens/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
