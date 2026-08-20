<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\Branding\HexColor;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class HexColorType extends Type
{
    public const NAME = 'hex_color';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if (!isset($fieldDeclaration['length'])) {
            $fieldDeclaration['length'] = 7;
        }

        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?HexColor
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return new HexColor((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof HexColor) {
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
