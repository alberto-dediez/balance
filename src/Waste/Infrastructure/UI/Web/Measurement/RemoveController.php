<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementRequest;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementService;

#[Route('/measurements/remove/{measurementId}', name: 'measurements_remove')]
class RemoveController extends AbstractController
{
    use GetMeasurementFromIdTrait;

    public function __invoke(
        string $measurementId, 
        MeasurementRepository $measurementRepository,
        RemoveMeasurementService $removeMeasurement,
        LoggedUser $loggedUser
    ): Response
    {   
        $measurement = $this->getMeasurementFromId($measurementId, $measurementRepository);

        try {
            $removeMeasurement(new RemoveMeasurementRequest($measurement->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la medición"));
        
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la medición"));
        }

        return $this->redirectToRoute('measurements');
    }
}
