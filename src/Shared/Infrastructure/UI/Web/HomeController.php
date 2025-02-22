<?php

namespace App\Shared\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/', name: 'home')]
class HomeController extends AbstractController
{
    public function __invoke(): Response
    {   
        return $this->render('@shared/home.html.twig');
    }
}
