<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Register\RegisterAnnouncementRequest;
use App\Changelog\Application\Announcement\Update\UpdateAnnouncementRequest;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait MapsManualUploadsTrait
{
    /**
     * @param FormInterface<mixed> $form
     */
    private function mapManualUpload(FormInterface $form, RegisterAnnouncementRequest|UpdateAnnouncementRequest $request): void
    {
        $file = $form->get('manual')->getData();

        if (!$file instanceof UploadedFile) {
            return;
        }

        $fileName = $file->getClientOriginalName();

        $request->manualLocalPath = $file->getPathname();
        $request->manualFileName = '' === $fileName ? basename($file->getPathname()) : $fileName;
    }
}
