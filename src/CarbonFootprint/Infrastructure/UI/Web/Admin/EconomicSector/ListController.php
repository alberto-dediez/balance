<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector;

use App\CarbonFootprint\Application\EconomicSector\List\ListEconomicSectorsRequest;
use App\CarbonFootprint\Application\EconomicSector\List\ListEconomicSectorsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListEconomicSectorsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector\Bulk\RemoveAction;
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

#[Route('/admin/economic-sectors', name: 'admin_economic_sectors')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListEconomicSectorsService $listEconomicSectors, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListEconomicSectorsRequest();
            }, ListEconomicSectorsType::class);

            $response = $listEconomicSectors($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->economicSectors,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/economic_sectors/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'economicSectors' => $response->economicSectors,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
