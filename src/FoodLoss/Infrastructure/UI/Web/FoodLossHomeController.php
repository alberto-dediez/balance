<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'food_loss_home', priority: 10)]
class FoodLossHomeController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@shared/home.html.twig');
    }
}
