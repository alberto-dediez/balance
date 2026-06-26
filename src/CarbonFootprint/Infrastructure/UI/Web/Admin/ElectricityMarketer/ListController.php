<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer;

use App\CarbonFootprint\Application\ElectricityMarketer\List\ListElectricityMarketersRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\List\ListElectricityMarketersService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListElectricityMarketersType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer\Bulk\RemoveAction;
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

#[Route('/admin/electricity-marketers', name: 'admin_electricity_marketers')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListElectricityMarketersService $listElectricityMarketers, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListElectricityMarketersRequest();
            }, ListElectricityMarketersType::class);

            $response = $listElectricityMarketers($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->electricityMarketers,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/electricity_marketers/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'electricityMarketers' => $response->electricityMarketers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
