<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector;

use App\CarbonFootprint\Application\EconomicSector\Remove\RemoveEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Remove\RemoveEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/economic-sectors/remove/{economicSectorId}', name: 'admin_economic_sectors_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetEconomicSectorFromIdTrait;

    public function __invoke(
        string $economicSectorId,
        EconomicSectorRepository $economicSectorRepository,
        RemoveEconomicSectorService $removeEconomicSector,
        LoggedUser $loggedUser,
    ): Response {
        $economicSector = $this->getEconomicSectorFromId($economicSectorId, $economicSectorRepository);

        try {
            $removeEconomicSector(new RemoveEconomicSectorRequest($economicSector->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el sector económico {economicSector}', ['{economicSector}' => $economicSector->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el sector económico {economicSector}', ['{economicSector}' => $economicSector->name()]));
        }

        return $this->redirectToRoute('admin_economic_sectors');
    }
}
