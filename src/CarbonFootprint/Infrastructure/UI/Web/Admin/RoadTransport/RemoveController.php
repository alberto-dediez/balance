<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport;

use App\CarbonFootprint\Application\RoadTransport\Remove\RemoveRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Remove\RemoveRoadTransportService;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/road-transports/remove/{roadTransportId}', name: 'admin_road_transports_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetRoadTransportFromIdTrait;

    public function __invoke(
        string $roadTransportId,
        RoadTransportRepository $roadTransportRepository,
        RemoveRoadTransportService $removeRoadTransport,
        LoggedUser $loggedUser,
    ): Response {
        $roadTransport = $this->getRoadTransportFromId($roadTransportId, $roadTransportRepository);

        try {
            $removeRoadTransport(new RemoveRoadTransportRequest($roadTransport->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el transporte {name}', ['{name}' => $roadTransport->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el transporte {name}', ['{name}' => $roadTransport->name()]));
        }

        return $this->redirectToRoute('admin_road_transports');
    }
}
