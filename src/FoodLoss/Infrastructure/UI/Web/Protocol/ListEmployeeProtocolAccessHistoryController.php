<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory\ListEmployeeProtocolAccessHistoryRequest;
use App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory\ListEmployeeProtocolAccessHistoryService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/protocols/{protocolId}/accesses/{employeeId}', name: 'protocols_employee_access_history')]
class ListEmployeeProtocolAccessHistoryController extends AbstractController
{
    public function __invoke(
        string $protocolId,
        string $employeeId,
        ListEmployeeProtocolAccessHistoryService $listHistory,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $listHistory(
                new ListEmployeeProtocolAccessHistoryRequest(
                    protocolId: new ProtocolId($protocolId),
                    employeeId: new EmployeeId($employeeId),
                    accountId: $account->id(),
                ),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/protocol/employee-access-history.html.twig', [
            'protocolId' => new ProtocolId($protocolId),
            'employee' => $response->employee,
            'accesses' => $response->accesses,
        ]);
    }
}
