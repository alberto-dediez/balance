<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Download;

use Symfony\Component\HttpFoundation\HeaderUtils;

use function Symfony\Component\String\u;

final class ContentDisposition
{
    private const string DEFAULT_FILE_NAME = 'download';

    private const string PATH_SEPARATORS = '#[/\\\\]#';

    private const string NON_FALLBACK_CHARACTERS = '#[^\x20-\x24\x26-\x2e\x30-\x3e\x40-\x5b\x5d-\x7e]#';

    public static function attachment(string $fileName): string
    {
        return self::header(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);
    }

    public static function inline(string $fileName): string
    {
        return self::header(HeaderUtils::DISPOSITION_INLINE, $fileName);
    }

    private static function header(string $disposition, string $fileName): string
    {
        $fileName = (string) preg_replace(self::PATH_SEPARATORS, '_', $fileName);

        return HeaderUtils::makeDisposition(
            $disposition,
            '' === $fileName ? self::DEFAULT_FILE_NAME : $fileName,
            self::asciiFallback($fileName),
        );
    }

    private static function asciiFallback(string $fileName): string
    {
        $fallback = (string) preg_replace(
            self::NON_FALLBACK_CHARACTERS,
            '_',
            u($fileName)->ascii()->toString(),
        );

        return '' === trim($fallback, '_') ? self::DEFAULT_FILE_NAME : $fallback;
    }
}
