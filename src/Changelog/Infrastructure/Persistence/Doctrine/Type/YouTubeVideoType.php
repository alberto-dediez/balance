<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\Doctrine\Type;

use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class YouTubeVideoType extends Type
{
    public const NAME = 'youtube_video';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?YouTubeVideo
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return YouTubeVideo::fromId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof YouTubeVideo) {
            return $value->id;
        }

        return null;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
