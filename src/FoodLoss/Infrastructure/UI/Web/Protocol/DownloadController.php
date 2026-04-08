<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/protocols/download/{protocolId}', name: 'protocols_download')]
class DownloadController extends AbstractController
{
    use GetProtocolFromIdTrait;

    public function __invoke(
        string $protocolId,
        ProtocolRepository $repository,
        LoggedUser $loggedUser,
    ): Response {
        $protocol = $this->getProtocolFromId($protocolId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $protocol);

        $path = $this->getParameter('app.uploads_dir') . '/' . $protocol->path();

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = 'protocolo-' . $protocol->id() . '.' . $extension;

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
        );

        return $response;
    }
}
