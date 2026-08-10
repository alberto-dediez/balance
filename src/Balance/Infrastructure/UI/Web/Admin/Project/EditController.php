<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Project;

use App\Balance\Application\AccountProject\List\ListAccountProjectsRequest;
use App\Balance\Application\AccountProject\List\ListAccountProjectsService;
use App\Balance\Application\Project\Update\UpdateProjectRequest;
use App\Balance\Application\Project\Update\UpdateProjectService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\Symfony\Form\UpdateProjectType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/projects/edit/{projectId}', name: 'admin_projects_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetProjectFromIdTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        UpdateProjectService $updateProject,
        ListAccountProjectsService $listAccountProjects,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $project);

        $accountProjectsResponse = $listAccountProjects(
            new ListAccountProjectsRequest(projectId: $project->id(), limit: 0),
            $loggedUser(),
        );

        $projectRequest = new UpdateProjectRequest(
            projectId: $project->id(),
            name: $project->name(),
            description: $project->description(),
            location: $project->location(),
            gallery: $project->gallery(),
            projectType: $project->projectType(),
            projectStatus: $project->projectStatus(),
            mitecoId: $project->mitecoId(),
            pricePerTonne: $project->pricePerTonne(),
            costPerTonne: $project->costPerTonne(),
        );

        try {
            $form = $this->createForm(UpdateProjectType::class, $projectRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProject, $loggedUser) {
                $projectRequest = $form->getData();

                $updateProject($projectRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Proyecto actualizado con éxito'));

                $this->warnIfPriceIsBelowCost($projectRequest->pricePerTonne, $projectRequest->costPerTonne);
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_projects');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/projects/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'accountProjects' => $accountProjectsResponse->accountProjects,
        ]);
    }

    private function warnIfPriceIsBelowCost(?float $pricePerTonne, ?float $costPerTonne): void
    {
        if (null === $pricePerTonne || null === $costPerTonne || $pricePerTonne >= $costPerTonne) {
            return;
        }

        $this->addFlash('warning', new TranslatableMessage(
            'El precio por tonelada ({price} €) es inferior al coste por tonelada ({cost} €).',
            [
                '{price}' => number_format($pricePerTonne, 2, ',', '.'),
                '{cost}' => number_format($costPerTonne, 2, ',', '.'),
            ],
        ));
    }
}
