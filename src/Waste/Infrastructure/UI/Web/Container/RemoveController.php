<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;

#[Route('/containers/remove/{containerId}', name: 'containers_remove')]
class RemoveController extends AbstractController
{
    use GetContainerFromIdTrait;

    public function __invoke(
        string $containerId, 
        ContainerRepository $containerRepository,
        RemoveContainerService $removeContainer,
        LoggedUser $loggedUser
    ): Response
    {   
        $container = $this->getContainerFromId($containerId, $containerRepository);

        try {
            $removeContainer(new RemoveContainerRequest($container->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado el contenedor"));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar el contenedor"));
        }

        return $this->redirectToRoute('containers');
    }
}
