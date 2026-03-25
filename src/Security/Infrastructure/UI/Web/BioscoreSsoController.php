<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Infrastructure\Bioscore\BioscoreSsoClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BioscoreSsoController extends AbstractController
{
    public function __construct(
        private readonly BioscoreSsoClient $ssoClient,
    ) {}

    #[Route(path: '/login/bioscore', name: 'app_login_bioscore')]
    public function login(Request $request): Response
    {
        return $this->redirect($this->ssoClient->getLoginUrl($request->getLocale()));
    }

    #[Route(path: '/login/bioscore/callback', name: 'app_login_bioscore_callback')]
    public function callback(): Response
    {
        // This route is handled by the BioscoreAuthenticator
        throw new \LogicException('This method should be intercepted by the BioscoreAuthenticator.');
    }
}
