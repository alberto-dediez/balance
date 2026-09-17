<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Employee;

use App\HumanResources\Application\Employee\Remove\RemoveEmployeeRequest;
use App\HumanResources\Application\Employee\Remove\RemoveEmployeeService;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/employees/remove/{employeeId}', name: 'employees_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetEmployeeFromIdTrait;

    public function __invoke(
        string $employeeId,
        EmployeeRepository $employeeRepository,
        RemoveEmployeeService $removeEmployee,
        LoggedUser $loggedUser,
    ): Response {
        $employee = $this->getEmployeeFromId($employeeId, $employeeRepository);

        try {
            $removeEmployee(new RemoveEmployeeRequest($employee->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Employee {name} has been deleted', ['{name}' => $employee->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('Could not delete employee {name}', ['{name}' => $employee->name()]));
        }

        return $this->redirectToRoute('employees');
    }
}
