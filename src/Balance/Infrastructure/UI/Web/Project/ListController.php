<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Project;

use App\Balance\Application\AccountProject\List\ListAccountProjectsRequest;
use App\Balance\Application\AccountProject\List\ListAccountProjectsService;
use App\Balance\Application\Project\List\ListProjectsRequest;
use App\Balance\Application\Project\List\ListProjectsService;
use App\Balance\Infrastructure\Symfony\Form\ListProjectsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects', name: 'projects')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListProjectsService $listProjects,
        ListAccountProjectsService $listAccountProjects,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter(
                $request,
                fn(): ListProjectsRequest => new ListProjectsRequest(),
                ListProjectsType::class,
                ['selectable' => true],
            );

            assert($filter instanceof ListProjectsRequest);

            $filter->selectedByAccountId = $account->id();

            $response = $listProjects($filter, $loggedUser());

            $accountProjectsResponse = $listAccountProjects(
                new ListAccountProjectsRequest(accountId: $account->id(), limit: 0),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $selectedProjectIds = array_map(
            static fn($accountProject): string => (string) $accountProject->projectId(),
            $accountProjectsResponse->accountProjects,
        );

        return $this->render('@balance/projects/list.html.twig', [
            'projects' => $response->projects,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'selectedProjectIds' => $selectedProjectIds,
        ]);
    }
}
