<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\Backup\MediaBackup;
use App\Library\Domain\Model\Backup\MediaBackupRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMediaBackupRepository implements MediaBackupRepository
{
    /** @var array<string, MediaBackup> */
    private array $backups = [];

    public function find(string $path): ?MediaBackup
    {
        return $this->backups[$path] ?? null;
    }

    public function findByMediaId(string $mediaId): ?MediaBackup
    {
        foreach ($this->backups as $backup) {
            if ($backup->mediaId() === $mediaId) {
                return $backup;
            }
        }

        return null;
    }

    public function save(MediaBackup $backup): void
    {
        $this->backups[$backup->path()] = $backup;
    }

    public function remove(MediaBackup $backup): void
    {
        unset($this->backups[$backup->path()]);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function findExistingPaths(array $paths): array
    {
        return array_values(array_filter(
            $paths,
            fn(string $path): bool => isset($this->backups[$path]),
        ));
    }

    /**
     * @return list<MediaBackup>
     */
    public function findRemovedBefore(\DateTimeImmutable $limit, int $max): array
    {
        $backups = array_values(array_filter(
            $this->backups,
            static fn(MediaBackup $backup): bool => null !== $backup->removedAt() && $backup->removedAt() <= $limit,
        ));

        usort(
            $backups,
            static fn(MediaBackup $a, MediaBackup $b): int => [$a->removedAt(), $a->path()] <=> [$b->removedAt(), $b->path()],
        );

        return array_slice($backups, 0, max(1, $max));
    }
}
