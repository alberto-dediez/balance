<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Application\MaterialityAssessment\Remove\RemoveMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Remove\RemoveMaterialityAssessmentService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/materiality-assessments/{materialityAssessmentId}/remove', name: 'materiality_assessments_remove', methods: ['POST'])]
class RemoveController extends AbstractController
{
    use GetMaterialityAssessmentFromIdTrait;

    public function __invoke(
        string $materialityAssessmentId,
        MaterialityAssessmentRepository $materialityAssessmentRepository,
        RemoveMaterialityAssessmentService $removeMaterialityAssessment,
        MaterialityStepRoute $stepRoute,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $materialityAssessment = $this->getMaterialityAssessmentFromId($materialityAssessmentId, $materialityAssessmentRepository);
        $step = $stepRoute->resolve($materialityAssessment->subjectType(), $materialityAssessment->carbonFootprintId());

        if (!$this->isCsrfTokenValid('materiality_assessment_remove_' . $materialityAssessment->id(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            ($removeMaterialityAssessment)(new RemoveMaterialityAssessmentRequest($materialityAssessment->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Cuestionario reiniciado.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MaterialityAssessmentNotEditableException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        }

        return $this->redirectToRoute($step['route'], $step['params']);
    }
}
