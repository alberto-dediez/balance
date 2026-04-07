<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\Protocol\Remove\RemoveProtocolRequest;
use App\FoodLoss\Application\Protocol\Remove\RemoveProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/protocols/remove/{protocolId}', name: 'protocols_remove')]
class RemoveController extends AbstractController
{
    use GetProtocolFromIdTrait;

    public function __invoke(
        string $protocolId,
        ProtocolRepository $protocolRepository,
        RemoveProtocolService $removeProtocol,
        LoggedUser $loggedUser,
    ): Response {
        $protocol = $this->getProtocolFromId($protocolId, $protocolRepository);

        try {
            $removeProtocol(new RemoveProtocolRequest($protocol->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el protocolo {name}', ['{name}' => $protocol->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el protocolo {name}', ['{name}' => $protocol->name()]));
        }

        return $this->redirectToRoute('protocols');
    }
}
