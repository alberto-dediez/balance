<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\DataTransformer;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\InvalidFontFamilyNameException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/** @implements DataTransformerInterface<FontFamilyName, string> */
readonly class FontFamilyNameTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        return $value instanceof FontFamilyName ? $value->value : '';
    }

    public function reverseTransform(mixed $value): ?FontFamilyName
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new FontFamilyName(trim($value));
        } catch (InvalidFontFamilyNameException $e) {
            throw new TransformationFailedException($e->getMessage(), previous: $e);
        }
    }
}
