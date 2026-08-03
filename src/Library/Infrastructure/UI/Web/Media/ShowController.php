<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Media\Download\DownloadMediaRequest;
use App\Library\Application\Media\Download\DownloadMediaResponse;
use App\Library\Application\Media\Download\DownloadMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library/media/{mediaId}/show', name: 'library_media_show')]
#[IsGranted('ROLE_USER')]
class ShowController extends AbstractController
{
    public function __invoke(
        string $mediaId,
        LoggedUser $loggedUser,
        DownloadMediaService $downloadMedia,
        Request $request,
    ): Response {
        try {
            $response = $downloadMedia(
                new DownloadMediaRequest(new MediaId($mediaId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        } catch (MediaNotFoundException|NotStoredMediaException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response, $request->query->getBoolean('download'));
    }

    private function streamFile(DownloadMediaResponse $response, bool $asAttachment): StreamedResponse
    {
        $stream = $response->stream;

        $streamedResponse = new StreamedResponse(static function () use ($stream): void {
            $output = fopen('php://output', 'wb');

            if (false !== $output) {
                stream_copy_to_stream($stream, $output);
                fclose($output);
            }

            fclose($stream);
        });

        $streamedResponse->headers->set('Content-Type', $response->mimeType);
        $streamedResponse->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                $asAttachment ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
                $response->fileName,
            ),
        );

        return $streamedResponse;
    }
}
