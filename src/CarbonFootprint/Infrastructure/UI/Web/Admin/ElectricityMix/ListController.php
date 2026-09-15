<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\List\ListElectricityMixesRequest;
use App\CarbonFootprint\Application\ElectricityMix\List\ListElectricityMixesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListElectricityMixesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix\Bulk\RemoveAction;
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

#[Route('/admin/electricity-mixes', name: 'admin_electricity_mixes')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListElectricityMixesService $listElectricityMixes, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListElectricityMixesRequest();
            }, ListElectricityMixesType::class);

            $response = $listElectricityMixes($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->electricityMixes,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/electricity_mixes/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'electricityMixes' => $response->electricityMixes,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
