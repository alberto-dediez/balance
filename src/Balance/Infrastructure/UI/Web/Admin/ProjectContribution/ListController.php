<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ProjectContribution;

use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Balance\Application\ProjectContribution\List\ListProjectContributionsService;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Infrastructure\Symfony\Form\ListAdminProjectContributionsType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contributions', name: 'admin_project_contributions')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListProjectContributionsService $listProjectContributions,
        ProjectRepository $projectRepository,
        AccountRepository $accountRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new ProjectContribution(id: ProjectContributionId::generate()));

        try {
            $projectChoices = [];

            foreach ($projectRepository->findByFilter(new ProjectFilter(limit: 0)) as $project) {
                $projectChoices[$project->name()] = $project->id();
            }

            $accountChoices = [];

            foreach ($accountRepository->findByFilter(new AccountFilter(limit: 0)) as $account) {
                $accountChoices[$account->name()] = $account->id();
            }

            [$filter, $form] = $this->filter(
                $request,
                fn(): ListProjectContributionsRequest => new ListProjectContributionsRequest(),
                ListAdminProjectContributionsType::class,
                ['projects' => $projectChoices, 'accounts' => $accountChoices],
            );

            $response = $listProjectContributions($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/contributions/list.html.twig', [
            'projectContributions' => $response->projectContributions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
