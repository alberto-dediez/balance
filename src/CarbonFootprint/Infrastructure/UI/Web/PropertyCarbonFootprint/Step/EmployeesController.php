<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
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

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/employees', name: 'property_carbon_footprints_employees', methods: ['GET', 'POST'])]
class EmployeesController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        AssignOrganizationalEmployeesService $assignEmployees,
        ListOrganizationalEmployeeContributionsService $listContributions,
        ListEmployeesService $listEmployees,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isProvidersAnswered()) {
            return $this->redirectToRoute(
                match (true) {
                    $propertyCarbonFootprint->status()->isScope3Answered() => 'property_carbon_footprints_providers',
                    $propertyCarbonFootprint->status()->isScope2Answered() => 'property_carbon_footprints_scope_3',
                    $propertyCarbonFootprint->status()->isScope1Answered() => 'property_carbon_footprints_scope_2',
                    default => 'property_carbon_footprints_scope_1',
                },
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($propertyCarbonFootprint->scope3Excluded()) {
            return $this->redirectToRoute(
                $propertyCarbonFootprint->status()->isEmployeesAnswered() ? 'property_carbon_footprints_mitigation_plan' : 'property_carbon_footprints_scope_3',
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employees_' . $propertyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $totalEmployees = $request->request->getInt('totalEmployees');

            if ($totalEmployees < 1) {
                $this->addFlash('danger', new TranslatableMessage('El total de empleados debe ser al menos 1'));

                return $this->redirectToRoute('property_carbon_footprints_employees', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
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
                        CarbonFootprintReportSubjectType::Property,
                        $propertyCarbonFootprintId,
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

                return $this->redirectToRoute('property_carbon_footprints_employees', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
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

            return $this->redirectToRoute('property_carbon_footprints_mitigation_plan', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        try {
            $employees = $listEmployees(new ListEmployeesRequest(accountId: $propertyCarbonFootprint->accountId(), limit: 0), $loggedUser())->employees;
            $overview = $listContributions(
                new ListOrganizationalEmployeeContributionsRequest(CarbonFootprintReportSubjectType::Property, $propertyCarbonFootprintId),
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
            '@carbon_footprint/property_carbon_footprints/step/employees.html.twig',
            [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'canRegisterEmployee' => $account instanceof Account && $this->isGranted(SecurityAction::Register->value, new Employee(id: EmployeeId::generate(), accountId: $account->id())),
                'readOnly' => $propertyCarbonFootprint->isCompleted(),
                'employees' => $employees,
                'assignedEmployeeIds' => $assignedEmployeeIds,
                'overview' => $overview,
                'confidenceLevels' => ConfidenceLevel::cases(),
                'employeeSources' => Scope::Scope3->employeeSources(),
                'excludedSources' => array_values(array_filter(
                    Scope::Scope3->employeeSources(),
                    static fn(Source $source): bool => $propertyCarbonFootprint->isSourceExcluded(Scope::Scope3, $source),
                )),
            ],
        );
    }
}
