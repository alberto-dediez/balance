<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\Allocations\ListCreditPurchaseAllocationsRequest;
use App\Balance\Application\CreditPurchase\Allocations\ListCreditPurchaseAllocationsService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\UI\Web\CreditPurchase\GetCreditPurchaseFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/credits/{creditPurchaseId}', name: 'admin_credit_purchases_view')]
class ViewController extends AbstractController
{
    use GetCreditPurchaseFromIdTrait;

    public function __invoke(
        string $creditPurchaseId,
        CreditPurchaseRepository $creditPurchaseRepository,
        ProjectRepository $projectRepository,
        ListCreditPurchaseAllocationsService $listCreditPurchaseAllocations,
        LoggedUser $loggedUser,
    ): Response {
        $creditPurchase = $this->getCreditPurchaseFromId($creditPurchaseId, $creditPurchaseRepository);

        try {
            $response = $listCreditPurchaseAllocations(
                new ListCreditPurchaseAllocationsRequest($creditPurchase->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $projectId = $creditPurchase->projectId();

        return $this->render('@balance/admin/credits/view.html.twig', [
            'creditPurchase' => $response->creditPurchase,
            'allocations' => $response->allocations,
            'allocatedTonnes' => $response->allocatedTonnes,
            'availableTonnes' => $response->availableTonnes,
            'project' => $projectId instanceof ProjectId ? $projectRepository->find($projectId) : null,
        ]);
    }
}
