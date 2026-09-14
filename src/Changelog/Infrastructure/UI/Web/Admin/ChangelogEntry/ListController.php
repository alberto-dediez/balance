<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\ChangelogEntry;

use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesRequest;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesService;
use App\Changelog\Infrastructure\Symfony\Form\ListChangelogEntriesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/changelog', name: 'admin_changelog')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListChangelogEntriesService $listChangelogEntries, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListChangelogEntriesRequest => new ListChangelogEntriesRequest(), ListChangelogEntriesType::class);

        try {
            $response = $listChangelogEntries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@changelog/admin/list.html.twig', [
            'entries' => $response->entries,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
