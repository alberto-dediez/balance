<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Application\Folder\Register\RegisterFolderRequest;
use App\Library\Application\Folder\Register\RegisterFolderService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNestingNotAllowedException;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Infrastructure\Symfony\Form\RegisterFolderType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/library/folders/register', name: 'library_folders_register')]
#[IsGranted('ROLE_USER')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterFolderService $registerFolder,
        FolderAuthorizer $authorizer,
        CurrentAccountFolders $folders,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $accountId = $loggedAccount->currentAccountId();

        $parent = null;
        $parentId = $request->query->getString('parent');

        if ('' !== $parentId) {
            $parent = $folders->find(new FolderId($parentId));

            if (!$parent instanceof Folder || $parent->isSubfolder()) {
                throw $this->createNotFoundException();
            }
        }

        $form = $this->createForm(RegisterFolderType::class, new RegisterFolderRequest(accountId: $accountId, parentId: $parent?->id()));

        try {
            $authorizer->denyUnlessCanDoThat(new Folder(id: FolderId::generate(), accountId: $accountId), Action::Register, $user);

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerFolder, $user, $accountId, $parent, $translator): bool {
                /** @var RegisterFolderRequest $registerRequest */
                $registerRequest = $form->getData();
                $registerRequest->accountId = $accountId;
                $registerRequest->parentId = $parent?->id();

                try {
                    $registerFolder($registerRequest, $user);
                } catch (FolderAlreadyExistsException) {
                    $form->get('name')->addError(new FormError($parent instanceof Folder
                        ? $translator->trans('Ya existe una subcarpeta con ese nombre en esta carpeta')
                        : $translator->trans('Ya existe una carpeta con ese nombre')));

                    return false;
                } catch (FolderNotFoundException|FolderNestingNotAllowedException $e) {
                    throw $this->createNotFoundException($e->getMessage());
                }

                $this->addFlash('success', $parent instanceof Folder
                    ? new TranslatableMessage('Subcarpeta creada con éxito')
                    : new TranslatableMessage('Carpeta creada con éxito'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('library_media');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@library/folder/register.html.twig', [
            'form' => $form->createView(),
            'parent' => $parent,
        ]);
    }
}
