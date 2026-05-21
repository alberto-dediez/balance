<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\List\ListSurplusesRequest;
use App\FoodLoss\Application\Surplus\List\ListSurplusesService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListSurplusesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/surpluses', name: 'surpluses')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListSurplusesService $listSurpluses, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): ListSurplusesRequest {
                $request = new ListSurplusesRequest();
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, ListSurplusesType::class);

            $response = $listSurpluses($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/surpluses/list.html.twig', [
            'surpluses' => $response->surpluses,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
