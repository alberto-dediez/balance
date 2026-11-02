<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi;

use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearRequest;
use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardRequest;
use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardService;
use App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects\ListKpiSubjectsRequest;
use App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects\ListKpiSubjectsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Infrastructure\Symfony\Form\DeclareBaseYearType;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi\View\KpiDashboardView;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprints/kpis/{propertyId?}', name: 'property_carbon_footprint_kpis', defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/kpis/{companyId?}', name: 'company_carbon_footprint_kpis', defaults: ['subjectType' => 'company'])]
class DashboardController extends AbstractController
{
    public function __invoke(
        Request $request,
        string $subjectType,
        ListKpiSubjectsService $listSubjects,
        GetCarbonFootprintKpiDashboardService $getDashboard,
        KpiDashboardView $dashboardView,
        LoggedUser $loggedUser,
        ?string $propertyId = null,
        ?string $companyId = null,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $subjectId = (CarbonFootprintReportSubjectType::Company === $type ? $companyId : $propertyId)
            ?? $request->query->getString(self::parameter($type));
        $user = $loggedUser();

        try {
            $subjects = $listSubjects(new ListKpiSubjectsRequest($type), $user)->options;

            if ('' === $subjectId) {
                if (1 === count($subjects)) {
                    return $this->redirectToRoute(self::route($type), [self::parameter($type) => $subjects[0]->id]);
                }

                return $this->render('@carbon_footprint/kpi/select.html.twig', [
                    'subjectType' => $type,
                    'subjects' => $subjects,
                    'route' => self::route($type),
                    'parameter' => self::parameter($type),
                ]);
            }

            $dashboard = $getDashboard(
                new GetCarbonFootprintKpiDashboardRequest(
                    subjectType: $type,
                    subjectId: $subjectId,
                    year: $request->query->has('year') ? $request->query->getInt('year') : null,
                    comparisonIndicator: IntensityIndicator::tryFrom($request->query->getString('indicator')),
                ),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $baseYear = $dashboard->baseYear;
        $baseYearForm = $this->createForm(
            DeclareBaseYearType::class,
            new DeclareBaseYearRequest(
                subjectType: $type,
                subjectId: $subjectId,
                year: $baseYear?->year() ?? $dashboard->selectedYear,
                justification: $baseYear?->justification(),
                recalculationThresholdPercent: $baseYear?->recalculationThresholdPercent() ?? '5',
                primaryIndicator: $baseYear?->primaryIndicator(),
            ),
            [
                'years' => $dashboard->availableYears,
                'declared' => null !== $baseYear,
                'action' => $this->generateUrl('base_year_declarations_declare', [
                    'subjectType' => $type->value,
                    'subjectId' => $subjectId,
                    'year' => $dashboard->selectedYear,
                ]),
            ],
        );

        return $this->render('@carbon_footprint/kpi/dashboard.html.twig', [
            'dashboard' => $dashboard,
            'subjects' => $subjects,
            'subjectType' => $type,
            'route' => self::route($type),
            'parameter' => self::parameter($type),
            'baseYearForm' => $baseYearForm->createView(),
            'view' => $dashboardView->build($dashboard),
        ]);
    }

    private static function route(CarbonFootprintReportSubjectType $type): string
    {
        return CarbonFootprintReportSubjectType::Company === $type ? 'company_carbon_footprint_kpis' : 'property_carbon_footprint_kpis';
    }

    private static function parameter(CarbonFootprintReportSubjectType $type): string
    {
        return CarbonFootprintReportSubjectType::Company === $type ? 'companyId' : 'propertyId';
    }
}
