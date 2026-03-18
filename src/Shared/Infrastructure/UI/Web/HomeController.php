<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'home')]
class HomeController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@shared/home.html.twig');
    }
}
