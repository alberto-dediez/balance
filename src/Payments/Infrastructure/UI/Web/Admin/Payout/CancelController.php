<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\Cancel\CancelPayoutRequest;
use App\Payments\Application\Payout\Cancel\CancelPayoutService;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutNotPendingException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payouts/{payoutId}/cancel', name: 'admin_payouts_cancel', methods: ['GET'])]
#[CsrfProtected('remove')]
class CancelController extends AbstractController
{
    public function __invoke(
        string $payoutId,
        CancelPayoutService $cancelPayout,
        LoggedUser $loggedUser,
    ): Response {
        try {
            $cancelPayout(new CancelPayoutRequest(new PayoutId($payoutId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Liquidación cancelada'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (PayoutNotPendingException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_payouts');
    }
}
