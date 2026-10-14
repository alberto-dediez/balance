<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Employee;

use App\HumanResources\Application\Employee\Register\RegisterEmployeeRequest;
use App\HumanResources\Application\Employee\Register\RegisterEmployeeService;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Symfony\Form\RegisterEmployeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/employees/register', name: 'employees_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterEmployeeService $registerEmployee, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $account = $loggedAccount();

        assert($account instanceof Account);

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Employee(id: EmployeeId::generate(), accountId: $account->id()));

        $form = $this->createForm(RegisterEmployeeType::class, new RegisterEmployeeRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerEmployee, $loggedUser, $account) {
                $registerRequest = $form->getData();

                $registerRequest->accountId = $account->id();

                $response = $registerEmployee($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Employee created successfully: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('employees');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@human_resources/employees/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
