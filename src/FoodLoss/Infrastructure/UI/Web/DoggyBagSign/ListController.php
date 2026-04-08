<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Application\DoggyBagSign\List\ListDoggyBagSignsRequest;
use App\FoodLoss\Application\DoggyBagSign\List\ListDoggyBagSignsService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Infrastructure\Symfony\Form\ListDoggyBagSignsType;
use App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign\Bulk\RemoveAction;
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

#[Route('/doggy-bag-signs', name: 'doggy_bag_signs')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListDoggyBagSignsService $listDoggyBagSigns,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        try {
            [$filter, $form] = $this->filter($request, function () use ($account): ListDoggyBagSignsRequest {
                $request = new ListDoggyBagSignsRequest();
                $request->accountId = $account?->id();

                return $request;
            }, ListDoggyBagSignsType::class);

            $response = $listDoggyBagSigns($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->doggyBagSigns,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $user);

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/doggy-bag-signs/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'doggyBagSigns' => $response->doggyBagSigns,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'dummy' => new DoggyBagSign(
                id: DoggyBagSignId::generate(),
                accountId: $account?->id(),
            ),
        ]);
    }
}
