<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\List\ListMitigationMeasuresRequest;
use App\CarbonFootprint\Application\MitigationMeasure\List\ListMitigationMeasuresService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListMitigationMeasuresType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure\Bulk\RemoveAction;
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

#[Route('/admin/mitigation-measures', name: 'admin_mitigation_measures')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMitigationMeasuresService $listMitigationMeasures, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMitigationMeasuresRequest();
            }, ListMitigationMeasuresType::class);

            $response = $listMitigationMeasures($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->mitigationMeasures,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measures/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'mitigationMeasures' => $response->mitigationMeasures,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
