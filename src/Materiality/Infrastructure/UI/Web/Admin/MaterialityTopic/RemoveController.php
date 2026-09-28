<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic;

use App\Materiality\Application\MaterialityTopic\Remove\RemoveMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Remove\RemoveMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-topics/remove/{materialityTopicId}', name: 'admin_materiality_topics_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMaterialityTopicFromIdTrait;

    public function __invoke(
        string $materialityTopicId,
        MaterialityTopicRepository $materialityTopicRepository,
        RemoveMaterialityTopicService $removeMaterialityTopic,
        LoggedUser $loggedUser,
    ): Response {
        $materialityTopic = $this->getMaterialityTopicFromId($materialityTopicId, $materialityTopicRepository);

        try {
            $removeMaterialityTopic(new RemoveMaterialityTopicRequest($materialityTopic->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la categoría de materialidad {name}', ['{name}' => $materialityTopic->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la categoría de materialidad {name}', ['{name}' => $materialityTopic->name()]));
        }

        return $this->redirectToRoute('admin_materiality_topics');
    }
}
