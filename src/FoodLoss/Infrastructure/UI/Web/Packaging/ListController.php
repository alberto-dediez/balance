<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Application\Packaging\List\ListPackagingsRequest;
use App\FoodLoss\Application\Packaging\List\ListPackagingsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListPackagingsType;
use App\FoodLoss\Infrastructure\UI\Web\Packaging\Bulk\RemoveAction;
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

#[Route('/packagings', name: 'packagings')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListPackagingsService $listPackagings, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn() => new ListPackagingsRequest(
                accountId: $loggedAccount()?->id(),
            ), ListPackagingsType::class);

            $response = $listPackagings($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->packagings,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/packagings/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'packagings' => $response->packagings,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
