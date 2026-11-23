<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Application\Folder\List\ListFoldersRequest;
use App\Library\Application\Folder\List\ListFoldersService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Infrastructure\Symfony\Form\ListFoldersType;
use App\Library\Infrastructure\UI\Web\Folder\Bulk\RemoveFolder;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library/folders', name: 'library_folders')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListFoldersService $listFolders,
        FolderAuthorizer $authorizer,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $accountId = $loggedAccount->currentAccountId();

        [$filter, $form] = $this->filter($request, function () use ($accountId) {
            return new ListFoldersRequest(accountId: $accountId, withoutAccount: !$accountId instanceof AccountId);
        }, ListFoldersType::class);

        try {
            $response = $listFolders($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $canManage = $authorizer->canDoThat(new Folder(id: FolderId::generate(), accountId: $accountId), Action::Register, $user);

        $bulkForm = null;

        if ($canManage) {
            $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
                'elements' => $response->folders,
                'actions' => [
                    new RemoveFolder(),
                ],
            ]);

            $needsRedirect = $this->bulkActions($request, $bulkForm, $user);

            if ($needsRedirect) {
                return $this->redirect($request->getRequestUri());
            }
        }

        return $this->render('@library/folder/list.html.twig', [
            'bulkForm' => $bulkForm,
            'folders' => $response->folders,
            'totalResults' => $response->totalResults,
            'canManage' => $canManage,
            'form' => $form->createView(),
        ]);
    }
}
