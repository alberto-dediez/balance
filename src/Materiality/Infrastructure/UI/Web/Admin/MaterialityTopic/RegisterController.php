<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic;

use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirectionRequiredException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNumberAlreadyUsedException;
use App\Materiality\Infrastructure\Symfony\Form\RegisterMaterialityTopicType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/materiality-topics/register', name: 'admin_materiality_topics_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterMaterialityTopicService $registerMaterialityTopic,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MaterialityTopic(MaterialityTopicId::generate()));

        $form = $this->createForm(RegisterMaterialityTopicType::class);

        try {
            $materialityTopicId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMaterialityTopic, $loggedUser, $translator, &$materialityTopicId) {
                try {
                    $response = $registerMaterialityTopic($form->getData(), $loggedUser());
                } catch (MaterialityTopicDirectionRequiredException) {
                    $form->get('direction')->addError(new FormError($translator->trans('La dirección es obligatoria en las categorías de alcance 3')));

                    return false;
                } catch (MaterialityTopicNumberAlreadyUsedException) {
                    $form->get('number')->addError(new FormError($translator->trans('Ya existe una categoría con ese número')));

                    return false;
                }

                $materialityTopicId = $response->materialityTopicId;

                $this->addFlash('success', new TranslatableMessage('Categoría de materialidad creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($materialityTopicId instanceof MaterialityTopicId) {
                    return $this->redirectToRoute('admin_materiality_topics_edit', ['materialityTopicId' => $materialityTopicId]);
                }

                return $this->redirectToRoute('admin_materiality_topics');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-topics/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
