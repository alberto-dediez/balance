<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion;

use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-questions/remove/{materialityQuestionId}', name: 'admin_materiality_questions_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMaterialityQuestionFromIdTrait;

    public function __invoke(
        string $materialityQuestionId,
        MaterialityQuestionRepository $materialityQuestionRepository,
        RemoveMaterialityQuestionService $removeMaterialityQuestion,
        LoggedUser $loggedUser,
    ): Response {
        $materialityQuestion = $this->getMaterialityQuestionFromId($materialityQuestionId, $materialityQuestionRepository);

        try {
            $removeMaterialityQuestion(new RemoveMaterialityQuestionRequest($materialityQuestion->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la pregunta de materialidad {text}', ['{text}' => $materialityQuestion->text()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la pregunta de materialidad {text}', ['{text}' => $materialityQuestion->text()]));
        }

        return $this->redirectToRoute('admin_materiality_questions');
    }
}
