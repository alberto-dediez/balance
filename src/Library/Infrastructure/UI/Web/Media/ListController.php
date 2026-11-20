<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Infrastructure\Symfony\Form\BulkMediaType;
use App\Library\Infrastructure\Symfony\Form\ListMediaType;
use App\Library\Infrastructure\UI\Web\Media\Bulk\BulkMediaRequest;
use App\Library\Infrastructure\UI\Web\Media\Bulk\MoveMedia;
use App\Library\Infrastructure\UI\Web\Media\Bulk\RemoveMedia;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library/media', name: 'library_media')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListMediaService $listMedia,
        CurrentAccountFolders $folders,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $accountId = $loggedAccount->currentAccountId();

        [$filter, $form] = $this->filter($request, function () use ($accountId) {
            return new ListMediaRequest(accountId: $accountId, withoutAccount: !$accountId instanceof AccountId);
        }, ListMediaType::class);

        if (
            null !== $filter->folder
            && ListMediaRequest::ALL_FOLDERS !== $filter->folder
            && !$folders->find(new FolderId($filter->folder)) instanceof Folder
        ) {
            $filter->folder = null;
        }

        $filter->applyFolderChoice();

        try {
            $response = $listMedia($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkMediaType::class, new BulkMediaRequest(), [
            'elements' => $response->media,
            'actions' => [
                new MoveMedia(),
                new RemoveMedia(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        $folderNames = [];

        foreach ($folders->all() as $folder) {
            $folderNames[(string) $folder->id()] = $folder->name();
        }

        return $this->render('@library/media/list.html.twig', [
            'bulkForm' => $bulkForm,
            'folderNames' => $folderNames,
            'media' => $response->media,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
