<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\View\ViewPayoutRequest;
use App\Payments\Application\Payout\View\ViewPayoutService;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutStatementLinesProvider;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payouts/{payoutId}', name: 'admin_payouts_view')]
class ViewController extends AbstractController
{
    public function __invoke(
        string $payoutId,
        ViewPayoutService $viewPayout,
        PayoutStatementLinesProvider $linesProvider,
        LoggedUser $loggedUser,
    ): Response {
        try {
            $response = $viewPayout(new ViewPayoutRequest(new PayoutId($payoutId)), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Liquidación no encontrada');
        }

        return $this->render('@payments/admin/payout/view.html.twig', [
            'payout' => $response->payout,
            'payee' => $response->payee,
            'lines' => $linesProvider->linesOf($response->payout->id()),
        ]);
    }
}
