<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

final readonly class HexColor implements \Stringable
{
    private const string PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    public string $value;

    public function __construct(string $value)
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new InvalidHexColorException($value);
        }

        $this->value = '#' . strtoupper(substr($value, 1));
    }

    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     */
    public static function fromRgb(array $rgb): self
    {
        return new self(sprintf('#%02X%02X%02X', ...$rgb));
    }

    public function shade(float $weight): self
    {
        return $this->mix(new self('#000000'), $weight);
    }

    public function tint(float $weight): self
    {
        return $this->mix(new self('#FFFFFF'), $weight);
    }

    public function mix(self $other, float $weight): self
    {
        $weight = max(0.0, min(1.0, $weight));

        [$r, $g, $b] = $this->toRgb();
        [$otherR, $otherG, $otherB] = $other->toRgb();

        return self::fromRgb([
            (int) round($r + ($otherR - $r) * $weight),
            (int) round($g + ($otherG - $g) * $weight),
            (int) round($b + ($otherB - $b) * $weight),
        ]);
    }

    /** @return array{0: int, 1: int, 2: int} */
    public function toRgb(): array
    {
        return [
            (int) hexdec(substr($this->value, 1, 2)),
            (int) hexdec(substr($this->value, 3, 2)),
            (int) hexdec(substr($this->value, 5, 2)),
        ];
    }

    public function contrastRatio(self $other): float
    {
        $lighter = max($this->relativeLuminance(), $other->relativeLuminance());
        $darker = min($this->relativeLuminance(), $other->relativeLuminance());

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function relativeLuminance(): float
    {
        $channels = array_map(
            static function (int $channel): float {
                $value = $channel / 255;

                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            $this->toRgb(),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
