<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Admin\Employee;

use App\HumanResources\Application\Employee\Update\UpdateEmployeeRequest;
use App\HumanResources\Application\Employee\Update\UpdateEmployeeService;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\HumanResources\Infrastructure\Symfony\Form\AdminUpdateEmployeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/employees/edit/{employeeId}', name: 'admin_employees_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetEmployeeFromIdTrait;

    public function __invoke(
        string $employeeId,
        EmployeeRepository $employeeRepository,
        UpdateEmployeeService $updateEmployee,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $employee = $this->getEmployeeFromId($employeeId, $employeeRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $employee);

        $employeeRequest = new UpdateEmployeeRequest(
            employeeId: $employee->id(),
            name: $employee->name(),
            email: $employee->email(),
            phone: $employee->phone(),
            street: $employee->address()?->street(),
            postalCode: $employee->address()?->postalCode(),
            city: $employee->address()?->city(),
            country: $employee->address()?->country(),
            accountId: $employee->accountId(),
            userId: $employee->userId(),
        );

        try {
            $form = $this->createForm(AdminUpdateEmployeeType::class, $employeeRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmployee, $loggedUser) {
                $employeeRequest = $form->getData();

                $updateEmployee($employeeRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Employee updated successfully'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_employees');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@human_resources/admin/employees/edit.html.twig', [
            'employee' => $employee,
            'form' => $form->createView(),
        ]);
    }
}
