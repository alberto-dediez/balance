<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Backup;

interface MediaBackupRepository
{
    public function find(string $path): ?MediaBackup;

    public function findByMediaId(string $mediaId): ?MediaBackup;

    public function save(MediaBackup $backup): void;

    public function remove(MediaBackup $backup): void;

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function findExistingPaths(array $paths): array;

    /**
     * @return list<MediaBackup>
     */
    public function findRemovedBefore(\DateTimeImmutable $limit, int $max): array;
}
