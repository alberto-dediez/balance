<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/remove', name: 'property_carbon_footprints_remove')]
class RemoveController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        RemovePropertyCarbonFootprintService $removePropertyCarbonFootprint,
        LoggedUser $loggedUser,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        try {
            $removePropertyCarbonFootprint(new RemovePropertyCarbonFootprintRequest($propertyCarbonFootprint->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el cálculo de huella de carbono'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el cálculo de huella de carbono'));
        }

        return $this->redirectToRoute('property_carbon_footprints');
    }
}
