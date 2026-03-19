<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/containers/remove/{containerId}', name: 'containers_remove')]
class RemoveController extends AbstractController
{
    use GetContainerFromIdTrait;

    public function __invoke(
        string $containerId,
        ContainerRepository $containerRepository,
        RemoveContainerService $removeContainer,
        LoggedUser $loggedUser,
    ): Response {
        $container = $this->getContainerFromId($containerId, $containerRepository);

        try {
            $removeContainer(new RemoveContainerRequest($container->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el contenedor'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el contenedor'));
        }

        return $this->redirectToRoute('containers');
    }
}
