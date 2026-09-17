<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Media\Remove\RemoveMediaRequest;
use App\Library\Application\Media\Remove\RemoveMediaService;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/library/media/remove/{mediaId}', name: 'library_media_remove')]
#[CsrfProtected('remove')]
#[IsGranted('ROLE_USER')]
class RemoveController extends AbstractController
{
    use GetMediaFromIdTrait;

    public function __invoke(
        string $mediaId,
        LoggedUser $loggedUser,
        MediaRepository $mediaRepository,
        RemoveMediaService $removeMedia,
    ): Response {
        $media = $this->getMediaFromId($mediaId, $mediaRepository);

        try {
            $removeMedia(new RemoveMediaRequest($media->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el archivo {media}', ['{media}' => $media->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el archivo {media}', ['{media}' => $media->name()]));
        }

        return $this->redirectToRoute('library_media');
    }
}
