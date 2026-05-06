<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause;

use App\FoodLoss\Application\SurplusCause\Remove\RemoveSurplusCauseRequest;
use App\FoodLoss\Application\SurplusCause\Remove\RemoveSurplusCauseService;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/surplus-causes/remove/{surplusCauseId}', name: 'admin_surplus_causes_remove')]
class RemoveController extends AbstractController
{
    use GetSurplusCauseFromIdTrait;

    public function __invoke(
        string $surplusCauseId,
        SurplusCauseRepository $surplusCauseRepository,
        RemoveSurplusCauseService $removeSurplusCause,
        LoggedUser $loggedUser,
    ): Response {
        $surplusCause = $this->getSurplusCauseFromId($surplusCauseId, $surplusCauseRepository);

        try {
            $removeSurplusCause(new RemoveSurplusCauseRequest($surplusCause->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la causa de excedente {surplusCause}', ['{surplusCause}' => $surplusCause->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la causa de excedente {surplusCause}', ['{surplusCause}' => $surplusCause->name()]));
        }

        return $this->redirectToRoute('admin_surplus_causes');
    }
}
