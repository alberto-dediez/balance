<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\ChangelogEntry;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryRequest;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/changelog/{slug}', name: 'admin_changelog_entry', requirements: ['slug' => '[a-z0-9-]+'])]
#[IsGranted('ROLE_ADMIN')]
class ShowController extends AbstractController
{
    public function __invoke(string $slug, ViewChangelogEntryService $viewChangelogEntry, ChangelogEntryAuthorizer $authorizer, LoggedUser $loggedUser): Response
    {
        $user = $loggedUser();

        try {
            $response = $viewChangelogEntry(new ViewChangelogEntryRequest(new ChangelogEntrySlug($slug)), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (ChangelogEntryNotFoundException|InvalidChangelogEntryException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('@changelog/admin/show.html.twig', [
            'entry' => $response->entry,
            'canViewTechnicalManual' => $authorizer->canDoThat(ChangelogEntry::placeholder(), Action::History, $user),
        ]);
    }
}
