<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Infrastructure\SSO\SSOClient;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SsoController extends AbstractController
{
    public function __construct(
        private readonly SSOClient $ssoClient,
    ) {}

    #[Route(path: '/login/sso', name: 'app_login_sso')]
    public function login(Request $request): Response
    {
        return $this->redirect($this->ssoClient->getLoginUrl(LocaleGetter::locale()));
    }

    #[Route(path: '/login/sso/callback', name: 'app_login_sso_callback')]
    public function callback(): Response
    {
        throw new \LogicException('This method should be intercepted by the SSOAuthenticator.');
    }
}
