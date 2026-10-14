<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Admin\Employee;

use App\HumanResources\Application\Employee\Register\RegisterEmployeeService;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Symfony\Form\AdminRegisterEmployeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/employees/register', name: 'admin_employees_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterEmployeeService $registerEmployee, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Employee(id: EmployeeId::generate()));

        $form = $this->createForm(AdminRegisterEmployeeType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerEmployee, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerEmployee($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Employee created successfully: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_employees');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@human_resources/admin/employees/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
