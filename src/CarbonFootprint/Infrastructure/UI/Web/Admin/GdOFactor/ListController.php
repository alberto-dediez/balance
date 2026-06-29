<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\List\ListGdOFactorsRequest;
use App\CarbonFootprint\Application\GdOFactor\List\ListGdOFactorsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListGdOFactorsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor\Bulk\RemoveAction;
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

#[Route('/admin/gdo-factors', name: 'admin_gdo_factors')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListGdOFactorsService $listGdOFactors, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListGdOFactorsRequest();
            }, ListGdOFactorsType::class);

            $response = $listGdOFactors($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->gdOFactors,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/gdo_factors/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'gdOFactors' => $response->gdOFactors,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
