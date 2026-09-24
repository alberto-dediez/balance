<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Announcement;

use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualResponse;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamAnnouncementManualTrait
{
    private function streamManual(DownloadAnnouncementManualResponse $response, bool $asAttachment): StreamedResponse
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
            $asAttachment
                ? ContentDisposition::attachment($response->fileName)
                : ContentDisposition::inline($response->fileName),
        );

        return $streamedResponse;
    }
}
