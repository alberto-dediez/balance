<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'api_login_check', methods: ['POST'])]
    public function login(AuthenticationUtils $authenticationUtils): void {}
}
