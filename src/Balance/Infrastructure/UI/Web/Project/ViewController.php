<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Project;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects/{projectId}', name: 'projects_view')]
class ViewController extends AbstractController
{
    use GetProjectFromIdTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        MediaRepository $mediaRepository,
        AccountProjectRepository $accountProjectRepository,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        $this->denyAccessUnlessGranted(Action::View->value, $project);

        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $gallery = array_filter(array_map(
            static fn($mediaId) => $mediaRepository->find($mediaId),
            $project->gallery(),
        ));

        $accountProjects = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $account->id(), projectId: $project->id(), limit: 1),
        );

        $accountProject = $accountProjects[0] ?? null;

        return $this->render('@balance/projects/view.html.twig', [
            'project' => $project,
            'gallery' => $gallery,
            'accountProject' => $accountProject,
        ]);
    }
}
