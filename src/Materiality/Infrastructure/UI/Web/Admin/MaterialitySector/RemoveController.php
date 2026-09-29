<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector;

use App\Materiality\Application\MaterialitySector\Remove\RemoveMaterialitySectorRequest;
use App\Materiality\Application\MaterialitySector\Remove\RemoveMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-sectors/remove/{materialitySectorId}', name: 'admin_materiality_sectors_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMaterialitySectorFromIdTrait;

    public function __invoke(
        string $materialitySectorId,
        MaterialitySectorRepository $materialitySectorRepository,
        RemoveMaterialitySectorService $removeMaterialitySector,
        LoggedUser $loggedUser,
    ): Response {
        $materialitySector = $this->getMaterialitySectorFromId($materialitySectorId, $materialitySectorRepository);

        try {
            $removeMaterialitySector(new RemoveMaterialitySectorRequest($materialitySector->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el sector de materialidad {name}', ['{name}' => $materialitySector->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el sector de materialidad {name}', ['{name}' => $materialitySector->name()]));
        }

        return $this->redirectToRoute('admin_materiality_sectors');
    }
}
