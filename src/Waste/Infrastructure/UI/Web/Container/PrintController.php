<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Container\PrintQr\PrintQrContainerRequest;
use App\Waste\Application\Container\PrintQr\PrintQrContainerResponse;
use App\Waste\Application\Container\PrintQr\PrintQrContainerService;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/containers/print/{containerId}', name: 'containers_print')]
class PrintController extends AbstractController
{
    use GetContainerFromIdTrait;

    public function __invoke(
        string $containerId,
        ContainerRepository $containerRepository,
        PrintQrContainerService $printContainer,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $container = $this->getContainerFromId($containerId, $containerRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $container);

        $containerRequest = new PrintQrContainerRequest(
            containerId: $container->id(),
        );

        try {
            $response = $printContainer($containerRequest, $loggedUser());

            if (!$response instanceof PrintQrContainerResponse || null === $response->path || empty($response->path)) {
                throw new \RuntimeException('Invalid response');
            }

            return $this->file($response->path, sprintf('%s.pdf', (string) $container->id()), ResponseHeaderBag::DISPOSITION_INLINE);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido generar el cartel del contenedor escogido'));
        }

        return $this->redirectToRoute('containers_edit', ['containerId' => $containerId]);
    }
}
