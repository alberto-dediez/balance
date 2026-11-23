<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Picker;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library/media/search', name: 'library_media_search', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    use MediaJsonResponseTrait;

    public function __invoke(
        ListMediaService $listMedia,
        CurrentAccountFolders $folders,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        UrlGeneratorInterface $urlGenerator,
        Request $request,
    ): JsonResponse {
        $accountId = $loggedAccount->currentAccountId();

        $filter = new ListMediaRequest(
            name: $request->query->getString('query') ?: null,
            accountId: $accountId,
            types: $this->requestedTypes($request),
            visibility: MediaVisibility::tryFrom($request->query->getString('visibility')),
            withoutAccount: !$accountId instanceof AccountId,
            includeShared: $request->query->getBoolean('includeShared') ?: null,
            limit: min(48, max(1, $request->query->getInt('limit', 24))),
        );

        $filter->page = max(0, $request->query->getInt('page'));
        $filter->searchInFolderNames = true;

        $folder = $request->query->getString('folder');

        if ('none' === $folder) {
            $filter->withoutFolder = true;
        } elseif ('' !== $folder) {
            $chosen = $folders->find(new FolderId($folder));

            if ($chosen instanceof Folder) {
                $filter->folderIds = [$chosen->id()];
            }
        }

        try {
            $response = $listMedia($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $folderNames = [];

        foreach ($folders->all() as $candidate) {
            $folderNames[(string) $candidate->id()] = $candidate->name();
        }

        return $this->json([
            'elements' => array_map(
                fn(Media $media): array => $this->toJsonElement(
                    $media,
                    $urlGenerator,
                    $media->folderId() instanceof FolderId ? ($folderNames[(string) $media->folderId()] ?? null) : null,
                ),
                $response->media,
            ),
            'totalResults' => $response->totalResults,
            'page' => $filter->page,
            'limit' => $filter->limit,
        ]);
    }

    /**
     * @return list<MediaType>
     */
    private function requestedTypes(Request $request): array
    {
        $types = array_filter(explode(',', $request->query->getString('types')));

        $result = [];

        foreach ($types as $type) {
            $mediaType = MediaType::tryFrom($type);

            if ($mediaType instanceof MediaType) {
                $result[] = $mediaType;
            }
        }

        return $result;
    }
}
