<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Project;

use App\Balance\Application\Project\Remove\RemoveProjectRequest;
use App\Balance\Application\Project\Remove\RemoveProjectService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/projects/remove/{projectId}', name: 'admin_projects_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetProjectFromIdTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        RemoveProjectService $removeProject,
        LoggedUser $loggedUser,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        try {
            $removeProject(new RemoveProjectRequest($project->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el proyecto {project}', ['{project}' => $project->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el proyecto {project}', ['{project}' => $project->name()]));
        }

        return $this->redirectToRoute('admin_projects');
    }
}
