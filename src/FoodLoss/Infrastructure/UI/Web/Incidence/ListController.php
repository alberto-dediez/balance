<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\List\ListIncidencesRequest;
use App\FoodLoss\Application\Incidence\List\ListIncidencesService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListIncidencesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/incidences', name: 'incidences')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListIncidencesService $listIncidences, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            $user = $loggedUser();

            list($filter, $form) = $this->filter($request, function () use ($loggedAccount) {
                $request = new ListIncidencesRequest();
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, ListIncidencesType::class);

            $response = $listIncidences($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/incidences/list.html.twig', [
            'incidences' => $response->incidences,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
