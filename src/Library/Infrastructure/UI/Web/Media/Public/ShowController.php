<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Public;

use App\Library\Application\Media\Download\DownloadMediaRequest;
use App\Library\Application\Media\Download\DownloadPublicMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\UI\Web\Media\StreamMediaTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the media marked as public to anyone, even without being logged in.
 */
#[Route('/library/public/media/{mediaId}', name: 'library_media_public_show', methods: ['GET'])]
class ShowController extends AbstractController
{
    use StreamMediaTrait;

    public function __invoke(
        string $mediaId,
        DownloadPublicMediaService $downloadPublicMedia,
        Request $request,
    ): Response {
        try {
            $response = $downloadPublicMedia(new DownloadMediaRequest(new MediaId($mediaId)));
        } catch (MediaNotFoundException|NotStoredMediaException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response, $request->query->getBoolean('download'));
    }
}
