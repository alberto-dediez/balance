<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\Statement\DownloadPayoutStatementRequest;
use App\Payments\Application\Payout\Statement\DownloadPayoutStatementService;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payouts/{payoutId}/statement', name: 'admin_payouts_statement')]
class StatementController extends AbstractController
{
    public function __invoke(
        string $payoutId,
        DownloadPayoutStatementService $downloadPayoutStatement,
        LoggedUser $loggedUser,
    ): BinaryFileResponse {
        try {
            $response = $downloadPayoutStatement(
                new DownloadPayoutStatementRequest(new PayoutId($payoutId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Liquidación no encontrada');
        }

        $file = new BinaryFileResponse($response->path);
        $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->filename);
        $file->deleteFileAfterSend(true);

        return $file;
    }
}
