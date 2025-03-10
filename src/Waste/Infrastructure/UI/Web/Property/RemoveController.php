<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Application\Property\Remove\RemovePropertyRequest;
use App\Waste\Application\Property\Remove\RemovePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;
use App\Waste\Infrastructure\UI\Web\Property\OnlyPropertiesFromAccountTrait;

#[Route('/properties/remove/{propertyId}', name: 'properties_remove')]
#[IsGranted("ROLE_OWNER")]
class RemoveController extends AbstractController
{
    use GetPropertyFromIdTrait, OnlyPropertiesFromAccountTrait;

    public function __invoke(
        string $propertyId, 
        PropertyRepository $propertyRepository,
        LoggedAccount $loggedAccount,
        RemovePropertyService $removeProperty
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->onlyPropertiesFromAccount($property, $loggedAccount());

        try {
            $removeProperty(new RemovePropertyRequest($property->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la propiedad {property}", ['{property}' => $property->name()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la propiedad {property}", ['{property}' => $property->name()]));
        }

        return $this->redirectToRoute('properties');
    }
}
