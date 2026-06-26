<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible;

use App\CarbonFootprint\Application\Combustible\List\ListCombustiblesRequest;
use App\CarbonFootprint\Application\Combustible\List\ListCombustiblesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListCombustiblesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible\Bulk\RemoveAction;
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

#[Route('/admin/combustibles', name: 'admin_combustibles')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCombustiblesService $listCombustibles, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListCombustiblesRequest();
            }, ListCombustiblesType::class);

            $response = $listCombustibles($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->combustibles,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/combustibles/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'combustibles' => $response->combustibles,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
