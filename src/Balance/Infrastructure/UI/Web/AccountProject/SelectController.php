<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\AccountProject;

use App\Balance\Application\AccountProject\Select\SelectProjectRequest;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\UI\Web\Project\GetProjectFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects/{projectId}/select', name: 'projects_select')]
class SelectController extends AbstractController
{
    use GetProjectFromIdTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        SelectProjectService $selectProject,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            $selectProject(new SelectProjectRequest($account->id(), $project->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha adherido el proyecto {project} a su cuenta', ['{project}' => $project->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido adherir el proyecto {project}', ['{project}' => $project->name()]));
        }

        $referer = $request->headers->get('referer');

        if (null !== $referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('projects_view', ['projectId' => $project->id()]);
    }
}
