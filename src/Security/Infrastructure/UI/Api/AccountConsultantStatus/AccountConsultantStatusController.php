<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountConsultantStatus;

use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusRequest;
use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/accounts/{accountId}/consultant-status', name: 'api_accounts_consultant_status', methods: ['PUT'])]
#[Areas(['admin'])]
#[OA\Put(description: '[Only for admins] Mark or unmark an account as consultant', summary: 'Change consultant status', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns the consultant status of the account',
    content: new Model(type: AccountConsultantStatusResponse::class),
)]
class AccountConsultantStatusController extends AbstractController
{
    public function __invoke(
        string $accountId,
        #[MapRequestPayload()]
        AccountConsultantStatusRequest $request,
        ChangeConsultantStatusService $changeConsultantStatus,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        try {
            $response = $changeConsultantStatus(
                new ChangeConsultantStatusRequest(
                    accountId: new AccountId($accountId),
                    consultant: $request->consultant,
                ),
                $loggedUser(),
            );
        } catch (ConsultantHasAssignedAccountsException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        return $this->json(new AccountConsultantStatusResponse(
            accountId: (string) $response->accountId,
            consultant: $response->consultant,
        ));
    }
}
