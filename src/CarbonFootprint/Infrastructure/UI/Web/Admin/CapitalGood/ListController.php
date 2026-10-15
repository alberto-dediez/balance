<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood;

use App\CarbonFootprint\Application\CapitalGood\List\ListCapitalGoodsRequest;
use App\CarbonFootprint\Application\CapitalGood\List\ListCapitalGoodsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListCapitalGoodsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/capital-goods', name: 'admin_capital_goods')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCapitalGoodsService $listCapitalGoods, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListCapitalGoodsRequest();
            }, ListCapitalGoodsType::class);

            $response = $listCapitalGoods($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->capitalGoods,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/capital_goods/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'capitalGoods' => $response->capitalGoods,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
