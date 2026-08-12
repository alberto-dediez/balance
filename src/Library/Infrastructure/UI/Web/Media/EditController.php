<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Media\Update\UpdateMediaRequest;
use App\Library\Application\Media\Update\UpdateMediaService;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Infrastructure\Symfony\Form\UpdateMediaType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/library/media/edit/{mediaId}', name: 'library_media_edit')]
#[IsGranted('ROLE_USER')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMediaFromIdTrait;

    public function __invoke(
        string $mediaId,
        MediaRepository $mediaRepository,
        LoggedUser $loggedUser,
        UpdateMediaService $updateMedia,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $media = $this->getMediaFromId($mediaId, $mediaRepository);

        $updateRequest = new UpdateMediaRequest(
            mediaId: $media->id(),
            name: $media->name(),
            description: $media->description(),
            alternativeText: $media->alternativeText(),
            visibility: $media->visibility(),
        );

        $form = $this->createForm(UpdateMediaType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMedia, $user): void {
                $mediaRequest = $form->getData();

                $updateMedia($mediaRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Archivo actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('library_media');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@library/media/edit.html.twig', [
            'media' => $media,
            'form' => $form->createView(),
        ]);
    }
}
