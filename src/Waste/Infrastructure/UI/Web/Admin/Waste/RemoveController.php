<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Application\Waste\Remove\RemoveWasteRequest;
use App\Waste\Application\Waste\Remove\RemoveWasteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/wastes/remove/{wasteId}', name: 'admin_wastes_remove')]
#[IsGranted("ROLE_ADMIN")]
class RemoveController extends AbstractController
{
    use GetWasteFromIdTrait;

    public function __invoke(
        string $wasteId, 
        WasteRepository $wasteRepository,
        RemoveWasteService $removeWaste
    ): Response
    {   
        $waste = $this->getWasteFromId($wasteId, $wasteRepository);

        try {
            $removeWaste(new RemoveWasteRequest($waste->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado el residuo {waste}", ['{waste}' => $waste->name()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar el residuo {waste}", ['{waste}' => $waste->name()]));
        }

        return $this->redirectToRoute('admin_wastes');
    }
}
