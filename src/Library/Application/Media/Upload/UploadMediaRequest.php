<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Upload;

use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class UploadMediaRequest implements Command
{
    public function __construct(
        public string $localPath = '',
        public string $fileName = '',
        public string $extension = '',
        public string $mimeType = '',
        public int $size = 0,
        public string $name = '',
        public ?string $description = null,
        public ?string $alternativeText = null,
        public MediaVisibility $visibility = MediaVisibility::Account,
        public ?AccountId $accountId = null,
        public ?UserId $uploadedBy = null,
    ) {}
}
