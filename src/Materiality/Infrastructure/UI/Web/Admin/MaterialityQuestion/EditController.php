<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion;

use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialityQuestionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-questions/edit/{materialityQuestionId}', name: 'admin_materiality_questions_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMaterialityQuestionFromIdTrait;

    public function __invoke(
        string $materialityQuestionId,
        MaterialityQuestionRepository $materialityQuestionRepository,
        UpdateMaterialityQuestionService $updateMaterialityQuestion,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $materialityQuestion = $this->getMaterialityQuestionFromId($materialityQuestionId, $materialityQuestionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $materialityQuestion);

        $materialityQuestionRequest = new UpdateMaterialityQuestionRequest(
            materialityQuestionId: $materialityQuestion->id(),
            criterion: $materialityQuestion->criterion(),
            topicId: $materialityQuestion->topicId(),
            text: $materialityQuestion->text(),
            help: $materialityQuestion->help(),
            answerScale: $materialityQuestion->answerScale(),
            weight: $materialityQuestion->weight(),
            position: $materialityQuestion->position(),
            active: $materialityQuestion->isActive(),
            sectorIds: $materialityQuestion->sectorIds(),
        );

        try {
            $form = $this->createForm(UpdateMaterialityQuestionType::class, $materialityQuestionRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMaterialityQuestion, $loggedUser) {
                $updateMaterialityQuestion($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Pregunta de materialidad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_materiality_questions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-questions/edit.html.twig', [
            'materialityQuestion' => $materialityQuestion,
            'form' => $form->createView(),
        ]);
    }
}
