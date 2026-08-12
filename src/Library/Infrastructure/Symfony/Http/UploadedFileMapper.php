<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Http;

use App\Library\Application\Media\Upload\UploadMediaRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileMapper
{
    public function __invoke(
        UploadedFile $file,
        ?AccountId $accountId = null,
        ?UserId $uploadedBy = null,
        ?UploadMediaRequest $request = null,
    ): UploadMediaRequest {
        $request ??= new UploadMediaRequest();

        $fileName = $file->getClientOriginalName();

        $request->localPath = $file->getPathname();
        $request->fileName = '' === $fileName ? basename($file->getPathname()) : $fileName;
        $request->extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $request->mimeType = $file->getMimeType() ?? $file->getClientMimeType();
        $request->size = (int) $file->getSize();
        $request->accountId = $accountId;
        $request->uploadedBy = $uploadedBy;

        if ('' === $request->name) {
            $request->name = $request->fileName;
        }

        return $request;
    }
}
