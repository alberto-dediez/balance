<?php

namespace App\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Shared\Domain\Model\Locale\Locale;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class LocaleType extends Type
{
    public const NAME = 'locale';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Locale
    {
        if ($value === null) {
            return null;
        }

        return Locale::tryFrom($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof Locale) {
            return $value->value;
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