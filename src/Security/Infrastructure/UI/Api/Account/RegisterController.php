<?php

namespace App\Security\Infrastructure\UI\Api\Account;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;

#[Route('/accounts', name: 'api_accounts_register', methods: ['POST'])]
#[OA\Post( 
    summary: "Generate a new account",
    description: "[Only for admins] Generate a new account",
    tags: ['Security']
)]
#[OA\Response(
    response: 200,
    description: 'Returns info about new registered account',
    content: new Model(type: AccountResponse::class)
)]
class RegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        RegisterAccountRequest $request,
        RegisterAccountService $register,
        LoggedUser $loggedUser
    ): JsonResponse
    {
        $response = $register($request, $loggedUser());

        return $this->json(new AccountResponse(
            accountId: $response->accountId,
            name: $response->name,
            active: $response->active
        ));
    }
}
