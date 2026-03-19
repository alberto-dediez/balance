<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\PDO\Tools;

class ColumnSanitizer
{
    public static function sanitize(array $headers): array
    {
        $sanitized = [];

        $counts = [];

        foreach ($headers as $i => $header) {
            $basename = $header instanceof \DateTimeInterface ? $header->format('Y-m-d H:i:s') : $header;

            $baseName = strtolower(trim((string) $basename));
            $baseName = preg_replace('/[\s\W]+/', '_', $baseName);
            $baseName = trim((string) $baseName, '_');

            if (empty($baseName) || 'row_id' === $baseName) {
                $baseName = 'col_' . $i;
            }

            if (!isset($counts[$baseName])) {
                $counts[$baseName] = 1;
                $sanitized[] = $baseName;

                continue;
            }

            ++$counts[$baseName];
            $sanitized[] = $baseName . '_' . $counts[$baseName];
        }

        return $sanitized;
    }
}
