<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Transport;

use App\CarbonFootprint\Application\Transport\Remove\RemoveTransportRequest;
use App\CarbonFootprint\Application\Transport\Remove\RemoveTransportService;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/transports/remove/{transportId}', name: 'admin_transports_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetTransportFromIdTrait;

    public function __invoke(
        string $transportId,
        TransportRepository $transportRepository,
        RemoveTransportService $removeTransport,
        LoggedUser $loggedUser,
    ): Response {
        $transport = $this->getTransportFromId($transportId, $transportRepository);

        try {
            $removeTransport(new RemoveTransportRequest($transport->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el transporte {name}', ['{name}' => $transport->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el transporte {name}', ['{name}' => $transport->name()]));
        }

        return $this->redirectToRoute('admin_transports');
    }
}
