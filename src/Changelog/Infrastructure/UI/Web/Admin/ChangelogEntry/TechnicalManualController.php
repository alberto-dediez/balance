<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\ChangelogEntry;

use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualRequest;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/changelog/{slug}/manual-tecnico', name: 'admin_changelog_tech_manual', requirements: ['slug' => '[a-z0-9-]+'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
class TechnicalManualController extends AbstractController
{
    public function __invoke(string $slug, ViewTechnicalManualService $viewTechnicalManual, LoggedUser $loggedUser): Response
    {
        try {
            $response = $viewTechnicalManual(new ViewTechnicalManualRequest(new ChangelogEntrySlug($slug)), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (ChangelogEntryNotFoundException|InvalidChangelogEntryException|ManualNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('@changelog/admin/tech_manual.html.twig', [
            'entry' => $response->entry,
            'markdown' => $response->markdown,
        ]);
    }
}
