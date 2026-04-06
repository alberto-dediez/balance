<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Application\CriticalPoint\Remove\RemoveCriticalPointRequest;
use App\FoodLoss\Application\CriticalPoint\Remove\RemoveCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/critical-points/remove/{criticalPointId}', name: 'critical_points_remove')]
class RemoveController extends AbstractController
{
    use GetCriticalPointFromIdTrait;

    public function __invoke(
        string $criticalPointId,
        CriticalPointRepository $criticalPointRepository,
        RemoveCriticalPointService $removeCriticalPoint,
        LoggedUser $loggedUser,
    ): Response {
        $criticalPoint = $this->getCriticalPointFromId($criticalPointId, $criticalPointRepository);

        try {
            $removeCriticalPoint(new RemoveCriticalPointRequest($criticalPoint->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el punto crítico {name}', ['{name}' => $criticalPoint->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el punto crítico {name}', ['{name}' => $criticalPoint->name()]));
        }

        return $this->redirectToRoute('critical_points');
    }
}
