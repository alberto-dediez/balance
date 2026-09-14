<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\ChangelogEntry;

use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualRequest;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/changelog/{slug}/manual', name: 'admin_changelog_manual', requirements: ['slug' => '[a-z0-9-]+'])]
#[IsGranted('ROLE_ADMIN')]
class ManualController extends AbstractController
{
    use StreamManualTrait;

    public function __invoke(string $slug, Request $request, DownloadManualService $downloadManual, LoggedUser $loggedUser): StreamedResponse
    {
        try {
            $response = $downloadManual(new DownloadManualRequest(new ChangelogEntrySlug($slug)), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (ChangelogEntryNotFoundException|InvalidChangelogEntryException|ManualNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->streamManual($response, $request->query->getBoolean('download'));
    }
}
