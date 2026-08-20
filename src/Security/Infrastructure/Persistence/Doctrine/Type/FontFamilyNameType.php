<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\Branding\FontFamilyName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class FontFamilyNameType extends Type
{
    public const NAME = 'font_family_name';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if (!isset($fieldDeclaration['length'])) {
            $fieldDeclaration['length'] = 100;
        }

        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?FontFamilyName
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return new FontFamilyName((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof FontFamilyName) {
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
