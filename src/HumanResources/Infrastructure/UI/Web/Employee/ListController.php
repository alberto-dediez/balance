<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Employee;

use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\HumanResources\Application\Employee\List\ListEmployeesService;
use App\HumanResources\Infrastructure\Symfony\Form\ListEmployeesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employees', name: 'employees')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListEmployeesService $listEmployees, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            $user = $loggedUser();

            list($filter, $form) = $this->filter($request, function () use ($loggedAccount) {
                $request = new ListEmployeesRequest();
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, ListEmployeesType::class);

            $response = $listEmployees($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@human_resources/employees/list.html.twig', [
            'employees' => $response->employees,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
