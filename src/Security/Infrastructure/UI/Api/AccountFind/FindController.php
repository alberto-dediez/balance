<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountFind;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\UI\Api\Account\AccountResponse;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/accounts/find', name: 'api_accounts_find', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: '[Only for admins] Find an account by its exact name', summary: 'Find an account by name', tags: ['Security'])]
#[OA\Response(
    response: 200,
    description: 'Returns info about a registered account',
    content: new Model(type: AccountResponse::class),
)]
class FindController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        FindRequest $request,
        AccountRepository $repository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Role::Admin->value);

        $account = $repository->findByName($request->name);

        if (!$account instanceof Account) {
            throw $this->createNotFoundException();
        }

        $consultantAccountId = $account->consultantAccountId();

        return $this->json(new AccountResponse(
            accountId: (string) $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            consultant: $account->isConsultant(),
            consultantAccountId: null === $consultantAccountId ? null : (string) $consultantAccountId,
        ));
    }
}
