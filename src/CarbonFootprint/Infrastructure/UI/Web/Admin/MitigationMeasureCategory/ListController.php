<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\List\ListMitigationMeasureCategoriesRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\List\ListMitigationMeasureCategoriesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListMitigationMeasureCategoriesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory\Bulk\RemoveAction;
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

#[Route('/admin/mitigation-measure-categories', name: 'admin_mitigation_measure_categories')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMitigationMeasureCategoriesService $listMitigationMeasureCategories, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMitigationMeasureCategoriesRequest();
            }, ListMitigationMeasureCategoriesType::class);

            $response = $listMitigationMeasureCategories($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->mitigationMeasureCategories,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measure_categories/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'mitigationMeasureCategories' => $response->mitigationMeasureCategories,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
