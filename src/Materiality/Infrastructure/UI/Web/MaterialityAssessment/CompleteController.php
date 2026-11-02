<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentIncompleteException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/materiality-assessments/{materialityAssessmentId}/complete', name: 'materiality_assessments_complete', methods: ['POST'])]
class CompleteController extends AbstractController
{
    use GetMaterialityAssessmentFromIdTrait;

    public function __invoke(
        string $materialityAssessmentId,
        MaterialityAssessmentRepository $materialityAssessmentRepository,
        CompleteMaterialityAssessmentService $completeMaterialityAssessment,
        MaterialityStepRoute $stepRoute,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $materialityAssessment = $this->getMaterialityAssessmentFromId($materialityAssessmentId, $materialityAssessmentRepository);
        $step = $stepRoute->resolve($materialityAssessment->subjectType(), $materialityAssessment->carbonFootprintId());

        if (!$this->isCsrfTokenValid('materiality_assessment_complete_' . $materialityAssessment->id(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            ($completeMaterialityAssessment)(new CompleteMaterialityAssessmentRequest($materialityAssessment->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Evaluación de materialidad completada.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MaterialityAssessmentIncompleteException) {
            $this->addFlash('danger', new TranslatableMessage('Faltan preguntas por responder.'));
        } catch (MaterialityAssessmentNotEditableException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        } catch (MaterialityAssessmentAlreadyCompletedException) {
        }

        return $this->redirectToRoute($step['route'], $step['params']);
    }
}
