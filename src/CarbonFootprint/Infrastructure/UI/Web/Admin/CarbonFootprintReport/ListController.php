<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\List\ListCarbonFootprintReportsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\List\ListCarbonFootprintReportsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListCarbonFootprintReportsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/carbon-footprint-reports', name: 'admin_carbon_footprint_reports')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListCarbonFootprintReportsService $listReports, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn(): ListCarbonFootprintReportsRequest => new ListCarbonFootprintReportsRequest(), ListCarbonFootprintReportsType::class);

            $response = $listReports($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/carbon_footprint_reports/list.html.twig', [
            'reports' => $response->reports,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
