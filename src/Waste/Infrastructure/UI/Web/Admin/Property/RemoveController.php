<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use App\Security\Application\Action;
use App\Waste\Domain\Model\Property\Property;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Property\PropertyId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Property\Remove\RemovePropertyRequest;
use App\Waste\Application\Property\Remove\RemovePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/properties/remove/{propertyId}', name: 'admin_properties_remove')]
class RemoveController extends AbstractController
{
    use GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId, 
        PropertyRepository $propertyRepository,
        RemovePropertyService $removeProperty,
        LoggedUser $loggedUser
    ): Response
    {   
        $this->denyAccessUnlessGranted(Action::Admin->value, new Property(PropertyId::generate()));
        
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        try {
            $removeProperty(new RemovePropertyRequest($property->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la propiedad {property}", ['{property}' => $property->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la propiedad {property}", ['{property}' => $property->name()]));
        }

        return $this->redirectToRoute('admin_properties');
    }
}
