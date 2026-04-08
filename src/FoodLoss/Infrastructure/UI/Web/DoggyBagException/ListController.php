<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException;

use App\FoodLoss\Application\DoggyBagException\List\ListDoggyBagExceptionsRequest;
use App\FoodLoss\Application\DoggyBagException\List\ListDoggyBagExceptionsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListDoggyBagExceptionsType;
use App\FoodLoss\Infrastructure\UI\Web\DoggyBagException\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doggy-bag-exceptions', name: 'doggy_bag_exceptions')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListDoggyBagExceptionsService $listDoggyBagExceptions, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () use ($loggedAccount) {
                $request = new ListDoggyBagExceptionsRequest();
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, ListDoggyBagExceptionsType::class);

            $response = $listDoggyBagExceptions($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->doggyBagExceptions,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/doggy-bag-exceptions/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'doggyBagExceptions' => $response->doggyBagExceptions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
