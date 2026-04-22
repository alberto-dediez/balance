<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class JobProgressKeyType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if (!isset($fieldDeclaration['length'])) {
            $fieldDeclaration['length'] = 500;
        }

        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?JobProgressKey
    {
        if (null === $value) {
            return null;
        }

        return JobProgressKey::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof JobProgressKey) {
            return (string) $value;
        }

        return null;
    }

    public function getName(): string
    {
        return 'job_progress_key';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
