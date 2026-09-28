<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic;

use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirectionRequiredException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialityTopicType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/materiality-topics/edit/{materialityTopicId}', name: 'admin_materiality_topics_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMaterialityTopicFromIdTrait;

    public function __invoke(
        string $materialityTopicId,
        MaterialityTopicRepository $materialityTopicRepository,
        UpdateMaterialityTopicService $updateMaterialityTopic,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $materialityTopic = $this->getMaterialityTopicFromId($materialityTopicId, $materialityTopicRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $materialityTopic);

        $materialityTopicRequest = new UpdateMaterialityTopicRequest(
            materialityTopicId: $materialityTopic->id(),
            scope: $materialityTopic->scope(),
            direction: $materialityTopic->direction(),
            name: $materialityTopic->name(),
            description: $materialityTopic->description(),
            position: $materialityTopic->position(),
            active: $materialityTopic->isActive(),
        );

        try {
            $form = $this->createForm(UpdateMaterialityTopicType::class, $materialityTopicRequest);

            $failed = false;

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMaterialityTopic, $loggedUser, $translator, &$failed) {
                try {
                    $updateMaterialityTopic($form->getData(), $loggedUser());
                } catch (MaterialityTopicDirectionRequiredException) {
                    $form->get('direction')->addError(new FormError($translator->trans('La dirección es obligatoria en las categorías de alcance 3')));

                    $failed = true;

                    return;
                }

                $this->addFlash('success', new TranslatableMessage('Categoría de materialidad actualizada con éxito'));
            });

            if ($needsRedirect && !$failed) {
                return $this->redirectToRoute('admin_materiality_topics');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-topics/edit.html.twig', [
            'materialityTopic' => $materialityTopic,
            'form' => $form->createView(),
        ]);
    }
}
