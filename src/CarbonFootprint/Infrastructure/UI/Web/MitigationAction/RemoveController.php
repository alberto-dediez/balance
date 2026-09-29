<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Remove\RemoveMitigationActionRequest;
use App\CarbonFootprint\Application\MitigationAction\Remove\RemoveMitigationActionService;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/mitigation-actions/{mitigationActionId}/remove', name: 'mitigation_actions_remove', methods: ['GET'])]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMitigationActionFromIdTrait;

    public function __invoke(
        string $mitigationActionId,
        MitigationActionRepository $mitigationActionRepository,
        RemoveMitigationActionService $removeMitigationAction,
        MitigationPlanStepRoute $stepRoute,
        LoggedUser $loggedUser,
    ): Response {
        $mitigationAction = $this->getMitigationActionFromId($mitigationActionId, $mitigationActionRepository);
        $step = $stepRoute->resolve($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId());

        try {
            $removeMitigationAction(new RemoveMitigationActionRequest($mitigationAction->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la medida {name}', ['{name}' => $mitigationAction->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MitigationPlanNotEditableException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        }

        return $this->redirectToRoute($step['route'], $step['params']);
    }
}
