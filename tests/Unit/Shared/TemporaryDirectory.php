<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

trait TemporaryDirectory
{
    private function temporaryDirectoryPath(string $prefix): string
    {
        return sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(8)) . '/';
    }

    private function removeTemporaryDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
