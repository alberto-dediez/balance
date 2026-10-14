<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Property;

use App\Property\Application\Property\Remove\RemovePropertyRequest;
use App\Property\Application\Property\Remove\RemovePropertyService;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/properties/remove/{propertyId}', name: 'properties_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId,
        PropertyRepository $propertyRepository,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        RemovePropertyService $removeProperty,
    ): Response {
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        try {
            $removeProperty(new RemovePropertyRequest($property->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la propiedad {property}', ['{property}' => $property->name()]));

        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la propiedad {property}', ['{property}' => $property->name()]));
        }

        return $this->redirectToRoute('properties');
    }
}
