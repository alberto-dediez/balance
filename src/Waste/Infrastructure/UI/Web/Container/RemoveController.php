<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Infrastructure\UI\Web\Property\OnlyPropertiesFromAccountTrait;

#[Route('/containers/remove/{containerId}', name: 'containers_remove')]
#[IsGranted("ROLE_OWNER")]
class RemoveController extends AbstractController
{
    use GetContainerFromIdTrait, OnlyPropertiesFromAccountTrait;

    public function __invoke(
        string $containerId, 
        ContainerRepository $containerRepository,
        PropertyRepository $propertyRepository,
        LoggedAccount $loggedAccount,
        RemoveContainerService $removeContainer
    ): Response
    {   
        $container = $this->getContainerFromId($containerId, $containerRepository);

        $property = $propertyRepository->find($container->propertyId());

        $this->onlyPropertiesFromAccount($property, $loggedAccount());

        try {
            $removeContainer(new RemoveContainerRequest($container->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado el contenedor"));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar el contenedor"));
        }

        return $this->redirectToRoute('containers');
    }
}
