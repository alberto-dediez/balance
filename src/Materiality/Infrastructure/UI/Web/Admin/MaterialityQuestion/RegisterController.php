<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion;

use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Infrastructure\Symfony\Form\RegisterMaterialityQuestionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-questions/register', name: 'admin_materiality_questions_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMaterialityQuestionService $registerMaterialityQuestion, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MaterialityQuestion(MaterialityQuestionId::generate()));

        $form = $this->createForm(RegisterMaterialityQuestionType::class, new RegisterMaterialityQuestionRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMaterialityQuestion, $loggedUser) {
                $response = $registerMaterialityQuestion($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Pregunta de materialidad creada correctamente: {text}', ['{text}' => $response->text]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_materiality_questions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-questions/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
