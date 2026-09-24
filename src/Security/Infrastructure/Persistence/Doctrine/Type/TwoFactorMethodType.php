<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\User\TwoFactorMethod;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TwoFactorMethodType extends Type
{
    public const NAME = 'two_factor_method';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?TwoFactorMethod
    {
        if (null === $value) {
            return null;
        }

        return TwoFactorMethod::tryFrom($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof TwoFactorMethod) {
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
