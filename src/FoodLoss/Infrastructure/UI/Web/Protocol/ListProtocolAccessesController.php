<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses\ListProtocolAccessesRequest;
use App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses\ListProtocolAccessesService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/protocols/{protocolId}/accesses', name: 'protocols_accesses')]
class ListProtocolAccessesController extends AbstractController
{
    use GetProtocolFromIdTrait;

    public function __invoke(
        string $protocolId,
        ListProtocolAccessesService $listProtocolAccesses,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $listProtocolAccesses(
                new ListProtocolAccessesRequest(
                    protocolId: new ProtocolId($protocolId),
                    accountId: $account->id(),
                ),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/protocol/accesses.html.twig', [
            'protocolId' => new ProtocolId($protocolId),
            'summaries' => $response->summaries,
        ]);
    }
}
