<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\Model\AggregateRootId;
use Doctrine\DBAL\Platforms\AbstractPlatform;

trait AggregateRootIdTypeTrait
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if (!isset($fieldDeclaration['length'])) {
            $fieldDeclaration['length'] = 36;
        }

        return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?AggregateRootId
    {
        if ($value === null) {
            return null;
        }

        return new ($this->getClassName())($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof AggregateRootId) {
            return (string) $value;
        }

        return null;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

}
