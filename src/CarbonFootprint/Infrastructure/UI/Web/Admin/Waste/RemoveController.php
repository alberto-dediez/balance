<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste;

use App\CarbonFootprint\Application\Waste\Remove\RemoveWasteRequest;
use App\CarbonFootprint\Application\Waste\Remove\RemoveWasteService;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/wastes/remove/{wasteId}', name: 'admin_wastes_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetWasteFromIdTrait;

    public function __invoke(
        string $wasteId,
        WasteRepository $wasteRepository,
        RemoveWasteService $removeWaste,
        LoggedUser $loggedUser,
    ): Response {
        $waste = $this->getWasteFromId($wasteId, $wasteRepository);

        try {
            $removeWaste(new RemoveWasteRequest($waste->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el residuo {waste}', ['{waste}' => $waste->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el residuo {waste}', ['{waste}' => $waste->name()]));
        }

        return $this->redirectToRoute('admin_wastes');
    }
}
