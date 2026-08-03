<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class MediaMother
{
    public static function uploadedMedia(): Media
    {
        return self::fromName('logo.png');
    }

    public static function fromName(string $name, string $mimeType = 'image/png'): Media
    {
        return self::from(MediaId::generate(), $name, $mimeType);
    }

    public static function fromNameAndId(string $name, string $id): Media
    {
        return self::from(new MediaId($id), $name);
    }

    public static function fromNameAndAccount(string $name, AccountId $accountId): Media
    {
        return self::from(MediaId::generate(), $name, 'image/png', $accountId);
    }

    public static function fromNameAndUploader(string $name, UserId $uploadedBy): Media
    {
        return self::from(MediaId::generate(), $name, 'image/png', null, $uploadedBy);
    }

    public static function document(): Media
    {
        return self::fromName('contract.pdf', 'application/pdf');
    }

    public static function random(): Media
    {
        return self::fromName('media-' . random_int(100, 999) . '.png');
    }

    public static function from(
        MediaId $mediaId,
        string $name = 'logo.png',
        string $mimeType = 'image/png',
        ?AccountId $accountId = null,
        ?UserId $uploadedBy = null,
    ): Media {
        $media = new Media(id: $mediaId);

        $media->upload(
            name: $name,
            fileName: $name,
            path: sprintf('shared/%s.%s', (string) $mediaId, pathinfo($name, PATHINFO_EXTENSION)),
            extension: pathinfo($name, PATHINFO_EXTENSION),
            mimeType: $mimeType,
            size: 2048,
            accountId: $accountId,
            uploadedBy: $uploadedBy,
        );

        return $media;
    }
}
