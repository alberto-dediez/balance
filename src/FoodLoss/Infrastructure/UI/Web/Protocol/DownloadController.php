<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\Protocol\Access\AccessProtocolRequest;
use App\FoodLoss\Application\Protocol\Access\AccessProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/protocols/download/{protocolId}', name: 'protocols_download')]
class DownloadController extends AbstractController
{
    public function __invoke(
        string $protocolId,
        AccessProtocolService $accessProtocol,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $accessProtocol(
                new AccessProtocolRequest(
                    protocolId: new ProtocolId($protocolId),
                    accountId: $account->id(),
                ),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $path = $this->getParameter('app.uploads_dir') . '/' . $response->path;

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = 'protocolo-' . $protocolId . '.' . $extension;

        $binaryResponse = new BinaryFileResponse($path);
        $binaryResponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
        );

        return $binaryResponse;
    }
}
