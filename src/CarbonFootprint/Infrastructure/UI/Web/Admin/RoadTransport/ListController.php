<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport;

use App\CarbonFootprint\Application\RoadTransport\List\ListRoadTransportsRequest;
use App\CarbonFootprint\Application\RoadTransport\List\ListRoadTransportsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListRoadTransportsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport\Bulk\RemoveAction;
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

#[Route('/admin/road-transports', name: 'admin_road_transports')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListRoadTransportsService $listRoadTransports, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListRoadTransportsRequest();
            }, ListRoadTransportsType::class);

            $response = $listRoadTransports($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->roadTransports,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/road_transports/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'roadTransports' => $response->roadTransports,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
