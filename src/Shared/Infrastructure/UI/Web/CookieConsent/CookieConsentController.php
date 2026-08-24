<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\CookieConsent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cookie-consent', name: 'public_cookie_consent', methods: ['POST'])]
class CookieConsentController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cookie_consent', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $decision = CookieConsentDecision::tryFrom($request->request->getString('decision'));

        if (null === $decision) {
            return new Response('Decisión de cookies no válida', Response::HTTP_BAD_REQUEST);
        }

        $response = $this->redirect($this->safeReferer($request));

        $response->headers->setCookie(Cookie::create(
            name: CookieConsentDecision::COOKIE_NAME,
            value: $decision->value,
            expire: time() + CookieConsentDecision::COOKIE_MAX_AGE,
            path: '/',
            secure: $request->isSecure(),
            httpOnly: false,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    private function safeReferer(Request $request): string
    {
        $referer = $request->headers->get('referer');
        $host = $request->getSchemeAndHttpHost();

        if (null !== $referer && ($referer === $host || str_starts_with($referer, $host . '/'))) {
            return $referer;
        }

        return $this->generateUrl('home');
    }
}
