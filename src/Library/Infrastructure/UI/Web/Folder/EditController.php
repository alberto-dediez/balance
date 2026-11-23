<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Application\Folder\Update\UpdateFolderRequest;
use App\Library\Application\Folder\Update\UpdateFolderService;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Library\Infrastructure\Symfony\Form\UpdateFolderType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/library/folders/edit/{folderId}', name: 'library_folders_edit')]
#[IsGranted('ROLE_USER')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetFolderFromIdTrait;

    public function __invoke(
        string $folderId,
        FolderRepository $folderRepository,
        FolderAuthorizer $authorizer,
        CurrentAccountFolders $folders,
        UpdateFolderService $updateFolder,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $folder = $this->getFolderFromId($folderId, $folderRepository);

        $parent = $folder->isSubfolder() && $folder->parentId() instanceof FolderId ? $folders->find($folder->parentId()) : null;

        $form = $this->createForm(UpdateFolderType::class, new UpdateFolderRequest(
            folderId: $folder->id(),
            name: $folder->name(),
        ));

        $failed = false;

        try {
            $authorizer->denyUnlessCanDoThat($folder, Action::Edit, $user);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateFolder, $user, $translator, $folder, &$failed): void {
                try {
                    $updateFolder($form->getData(), $user);
                } catch (FolderAlreadyExistsException) {
                    $form->get('name')->addError(new FormError($folder->isSubfolder()
                        ? $translator->trans('Ya existe una subcarpeta con ese nombre en esta carpeta')
                        : $translator->trans('Ya existe una carpeta con ese nombre')));

                    $failed = true;

                    return;
                }

                $this->addFlash('success', new TranslatableMessage('Carpeta actualizada con éxito'));
            });

            if ($needsRedirect && !$failed) {
                return $this->redirectToRoute('library_media');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@library/folder/edit.html.twig', [
            'folder' => $folder,
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }
}
