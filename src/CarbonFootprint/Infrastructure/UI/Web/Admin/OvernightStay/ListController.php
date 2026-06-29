<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay;

use App\CarbonFootprint\Application\OvernightStay\List\ListOvernightStaysRequest;
use App\CarbonFootprint\Application\OvernightStay\List\ListOvernightStaysService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListOvernightStaysType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay\Bulk\RemoveAction;
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

#[Route('/admin/overnight-stays', name: 'admin_overnight_stays')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListOvernightStaysService $listOvernightStays, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListOvernightStaysRequest();
            }, ListOvernightStaysType::class);

            $response = $listOvernightStays($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->overnightStays,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/overnight_stays/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'overnightStays' => $response->overnightStays,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
