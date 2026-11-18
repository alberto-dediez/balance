<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

use App\Shared\Application\Ai\AiAttachment;

final readonly class ToolResult
{
    public const int TEXT_MAX_LENGTH = 20000;
    public const int ITEMS_MAX_COUNT = 20;

    /**
     * @param array<mixed>          $data
     * @param array<string, string> $references
     * @param list<AiAttachment>    $attachments
     */
    public function __construct(
        public string $summary,
        public array $data = [],
        public array $references = [],
        public ?ToolLink $link = null,
        public array $attachments = [],
    ) {}

    /** @return array{0: string, 1: bool} */
    public static function truncate(string $text): array
    {
        if (mb_strlen($text) <= self::TEXT_MAX_LENGTH) {
            return [$text, false];
        }

        return [mb_substr($text, 0, self::TEXT_MAX_LENGTH), true];
    }

    /**
     * @template T
     *
     * @param list<T>                         $items
     * @param \Closure(T): \DateTimeInterface $dateOf
     *
     * @return array{0: list<T>, 1: bool}
     */
    public static function latest(array $items, \Closure $dateOf): array
    {
        usort($items, static fn(mixed $a, mixed $b): int => $dateOf($a) <=> $dateOf($b));

        if (count($items) <= self::ITEMS_MAX_COUNT) {
            return [$items, false];
        }

        return [array_slice($items, -self::ITEMS_MAX_COUNT), true];
    }
}
