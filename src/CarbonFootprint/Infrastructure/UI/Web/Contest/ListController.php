<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\List\ListContestsRequest;
use App\CarbonFootprint\Application\Contest\List\ListContestsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListContestsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/contests', name: 'contests')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        ListContestsService $listContests,
        Request $request,
    ): Response {
        [$filter, $form] = $this->filter($request, fn(): ListContestsRequest => new ListContestsRequest(), ListContestsType::class);

        try {
            $response = $listContests($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/contests/list.html.twig', [
            'contests' => $response->contests,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
