<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder;

use App\Library\Application\Folder\Remove\RemoveFolderRequest;
use App\Library\Application\Folder\Remove\RemoveFolderService;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/library/folders/remove/{folderId}', name: 'library_folders_remove')]
#[CsrfProtected('remove')]
#[IsGranted('ROLE_USER')]
class RemoveController extends AbstractController
{
    use GetFolderFromIdTrait;

    public function __invoke(
        string $folderId,
        LoggedUser $loggedUser,
        FolderRepository $folderRepository,
        RemoveFolderService $removeFolder,
    ): Response {
        $folder = $this->getFolderFromId($folderId, $folderRepository);

        try {
            $removeFolder(new RemoveFolderRequest($folder->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la carpeta {folder}', ['{folder}' => $folder->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la carpeta {folder}', ['{folder}' => $folder->name()]));
        }

        return $this->redirectToRoute('library_folders');
    }
}
