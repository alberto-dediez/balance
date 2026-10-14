<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountConsultant;

use App\Security\Application\Account\AssignConsultant\AssignConsultantRequest;
use App\Security\Application\Account\AssignConsultant\AssignConsultantService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountIsNotConsultantException;
use App\Security\Domain\Model\Account\ConsultantCannotHaveConsultantException;
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

#[Route('/accounts/{accountId}/consultant', name: 'api_accounts_assign_consultant', methods: ['PUT'])]
#[Areas(['admin'])]
#[OA\Put(description: '[Only for admins] Assign or clear the consultant of an account', summary: 'Assign consultant to account', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns the consultant assigned to the account',
    content: new Model(type: AccountConsultantResponse::class),
)]
class AccountConsultantController extends AbstractController
{
    public function __invoke(
        string $accountId,
        #[MapRequestPayload()]
        AccountConsultantRequest $request,
        AssignConsultantService $assignConsultant,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        try {
            $response = $assignConsultant(
                new AssignConsultantRequest(
                    accountId: new AccountId($accountId),
                    consultantAccountId: null === $request->consultantAccountId ? null : new AccountId($request->consultantAccountId),
                ),
                $loggedUser(),
            );
        } catch (AccountIsNotConsultantException|ConsultantCannotHaveConsultantException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        $consultantAccountId = $response->consultantAccountId;

        return $this->json(new AccountConsultantResponse(
            accountId: (string) $response->accountId,
            consultantAccountId: null === $consultantAccountId ? null : (string) $consultantAccountId,
        ));
    }
}
