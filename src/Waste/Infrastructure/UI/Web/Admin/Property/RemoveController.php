<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Application\Property\Remove\RemovePropertyRequest;
use App\Waste\Application\Property\Remove\RemovePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/properties/remove/{propertyId}', name: 'admin_properties_remove')]
#[IsGranted("ROLE_ADMIN")]
class RemoveController extends AbstractController
{
    use GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId, 
        PropertyRepository $propertyRepository,
        RemovePropertyService $removeProperty
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        try {
            $removeProperty(new RemovePropertyRequest($property->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la propiedad {property}", ['{property}' => $property->name()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la propiedad {property}", ['{property}' => $property->name()]));
        }

        return $this->redirectToRoute('admin_properties');
    }
}
