<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\Remittance\EmptyRemittanceException;
use App\Payments\Application\Payout\Remittance\GeneratePayoutRemittanceRequest;
use App\Payments\Application\Payout\Remittance\GeneratePayoutRemittanceService;
use App\Payments\Domain\Model\Payout\PayeeNotPayableException;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payouts-remittance', name: 'admin_payouts_remittance')]
class RemittanceController extends AbstractController
{
    public function __invoke(
        GeneratePayoutRemittanceService $generateRemittance,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $response = $generateRemittance(
                new GeneratePayoutRemittanceRequest(
                    format: (string) $request->query->get('format', 'sepa'),
                    payoutIds: $this->payoutIds($request),
                ),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (EmptyRemittanceException|PayeeNotPayableException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('admin_payouts');
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Formato de remesa desconocido');
        }

        return new Response($response->content, Response::HTTP_OK, [
            'Content-Type' => $response->contentType,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $response->filename),
        ]);
    }

    /** @return list<PayoutId> */
    private function payoutIds(Request $request): array
    {
        $ids = $request->query->get('ids');

        if (!is_string($ids) || '' === $ids) {
            return [];
        }

        return array_map(static fn(string $id): PayoutId => new PayoutId($id), explode(',', $ids));
    }
}
