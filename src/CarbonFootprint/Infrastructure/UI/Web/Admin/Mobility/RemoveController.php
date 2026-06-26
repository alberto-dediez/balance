<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility;

use App\CarbonFootprint\Application\Mobility\Remove\RemoveMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Remove\RemoveMobilityService;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mobilities/remove/{mobilityId}', name: 'admin_mobilities_remove')]
class RemoveController extends AbstractController
{
    use GetMobilityFromIdTrait;

    public function __invoke(
        string $mobilityId,
        MobilityRepository $mobilityRepository,
        RemoveMobilityService $removeMobility,
        LoggedUser $loggedUser,
    ): Response {
        $mobility = $this->getMobilityFromId($mobilityId, $mobilityRepository);

        try {
            $removeMobility(new RemoveMobilityRequest($mobility->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la movilidad {mobility}', ['{mobility}' => $mobility->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la movilidad {mobility}', ['{mobility}' => $mobility->name()]));
        }

        return $this->redirectToRoute('admin_mobilities');
    }
}
