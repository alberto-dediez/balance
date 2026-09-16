<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Sso;

use App\Security\Infrastructure\Sso\SsoProviderRegistry;
use App\Security\Infrastructure\Sso\UnknownSsoProviderException;
use App\Security\Infrastructure\Symfony\Security\SsoAuthenticator;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SsoController extends AbstractController
{
    public function __construct(
        private readonly SsoProviderRegistry $providers,
        private readonly string $defaultLocale,
    ) {}

    #[Route(path: '/login/sso/{provider}', name: 'app_login_sso', requirements: ['provider' => '[a-z]+'], methods: ['GET'])]
    public function start(Request $request, string $provider): Response
    {
        try {
            $ssoProvider = $this->providers->get($provider);
        } catch (UnknownSsoProviderException) {
            throw $this->createNotFoundException();
        }

        if (!$ssoProvider->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $request->getSession()->set(SsoAuthenticator::LOCALE_SESSION_KEY, $this->requestedLocale($request));

        return $this->redirect($ssoProvider->loginUrl());
    }

    #[Route(path: '/login/sso/{provider}/callback', name: 'app_login_sso_callback', requirements: ['provider' => '[a-z]+'], methods: ['GET'])]
    public function callback(): never
    {
        throw new \LogicException('Intercepted by SsoAuthenticator.');
    }

    private function requestedLocale(Request $request): string
    {
        $locale = Locale::tryFrom((string) $request->query->get('locale'));

        return $locale instanceof Locale ? $locale->value : $this->defaultLocale;
    }
}
