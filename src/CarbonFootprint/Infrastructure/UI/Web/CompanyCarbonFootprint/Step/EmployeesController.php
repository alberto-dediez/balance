<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\HumanResources\Application\Employee\List\ListEmployeesService;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/employees', name: 'company_carbon_footprints_employees', methods: ['GET', 'POST'])]
class EmployeesController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        AssignOrganizationalEmployeesService $assignEmployees,
        ListOrganizationalEmployeeContributionsService $listContributions,
        ListEmployeesService $listEmployees,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isProvidersAnswered()) {
            return $this->redirectToRoute(
                match (true) {
                    $companyCarbonFootprint->status()->isScope3Answered() => 'company_carbon_footprints_providers',
                    $companyCarbonFootprint->status()->isScope2Answered() => 'company_carbon_footprints_scope_3',
                    $companyCarbonFootprint->status()->isScope1Answered() => 'company_carbon_footprints_scope_2',
                    default => 'company_carbon_footprints_scope_1',
                },
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($companyCarbonFootprint->scope3Excluded()) {
            return $this->redirectToRoute(
                $companyCarbonFootprint->status()->isEmployeesAnswered() ? 'company_carbon_footprints_mitigation_plan' : 'company_carbon_footprints_scope_3',
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employees_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $totalEmployees = $request->request->getInt('totalEmployees');

            if ($totalEmployees < 1) {
                $this->addFlash('danger', new TranslatableMessage('El total de empleados debe ser al menos 1'));

                return $this->redirectToRoute('company_carbon_footprints_employees', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
            }

            $employeeIds = [];
            foreach ($request->request->all('employees')['employeeIds'] ?? [] as $employeeId) {
                if (is_string($employeeId) && '' !== $employeeId) {
                    $employeeIds[] = new EmployeeId($employeeId);
                }
            }

            try {
                $response = $assignEmployees(
                    new AssignOrganizationalEmployeesRequest(
                        CarbonFootprintReportSubjectType::Company,
                        $companyCarbonFootprintId,
                        $employeeIds,
                        $totalEmployees,
                        ConfidenceLevel::tryFrom($request->request->getInt('confidenceLevel')) ?? ConfidenceLevel::NinetyFive,
                    ),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido asignar los empleados: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                return $this->redirectToRoute('company_carbon_footprints_employees', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
            }

            if ($response->overnightStayCatalogMissing) {
                $this->addFlash('warning', new TranslatableMessage('No hay ninguna pernoctación del equipo organizador en el catálogo: las noches de hotel no se han contabilizado'));
            }

            if ([] !== $response->skippedMobilities) {
                $this->addFlash('warning', new TranslatableMessage(
                    'Sin factor de emisión para {items}: sus datos de empleados no se han contabilizado',
                    ['{items}' => implode(', ', $response->skippedMobilities)],
                ));
            }

            if ([] !== $response->fallbackYears) {
                $fallbackYears = [];
                foreach ($response->fallbackYears as $name => $year) {
                    $fallbackYears[] = sprintf('%s (%d)', $name, $year);
                }

                $this->addFlash('info', new TranslatableMessage(
                    'Sin factor de emisión del año de la huella; se ha usado el del año más cercano para {items}',
                    ['{items}' => implode(', ', $fallbackYears)],
                ));
            }

            return $this->redirectToRoute('company_carbon_footprints_mitigation_plan', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        try {
            $employees = $listEmployees(new ListEmployeesRequest(accountId: $companyCarbonFootprint->accountId(), limit: 0), $loggedUser())->employees;
            $overview = $listContributions(
                new ListOrganizationalEmployeeContributionsRequest(CarbonFootprintReportSubjectType::Company, $companyCarbonFootprintId),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $account = $loggedAccount();

        $assignedEmployeeIds = [];
        foreach ($overview->contributions as $contribution) {
            $employeeId = $contribution->employeeId();

            if (null !== $employeeId && !$contribution->status()->isIgnored()) {
                $assignedEmployeeIds[(string) $employeeId] = true;
            }
        }

        return $this->render(
            '@carbon_footprint/company_carbon_footprints/step/employees.html.twig',
            [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'canRegisterEmployee' => $account instanceof Account && $this->isGranted(SecurityAction::Register->value, new Employee(id: EmployeeId::generate(), accountId: $account->id())),
                'readOnly' => $companyCarbonFootprint->isCompleted(),
                'employees' => $employees,
                'assignedEmployeeIds' => $assignedEmployeeIds,
                'overview' => $overview,
                'confidenceLevels' => ConfidenceLevel::cases(),
                'employeeSources' => Scope::Scope3->employeeSources(),
                'excludedSources' => array_values(array_filter(
                    Scope::Scope3->employeeSources(),
                    static fn(Source $source): bool => $companyCarbonFootprint->isSourceExcluded(Scope::Scope3, $source),
                )),
            ],
        );
    }
}
