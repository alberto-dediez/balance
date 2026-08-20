<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\DataTransformer;

use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\InvalidHexColorException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/** @implements DataTransformerInterface<HexColor, string> */
readonly class HexColorTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        return $value instanceof HexColor ? $value->value : '';
    }

    public function reverseTransform(mixed $value): ?HexColor
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new HexColor(trim($value));
        } catch (InvalidHexColorException $e) {
            throw new TransformationFailedException($e->getMessage(), previous: $e);
        }
    }
}
