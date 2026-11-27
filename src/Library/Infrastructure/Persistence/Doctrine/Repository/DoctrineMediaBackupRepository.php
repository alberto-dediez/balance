<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\Backup\MediaBackup;
use App\Library\Domain\Model\Backup\MediaBackupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMediaBackupRepository implements MediaBackupRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function find(string $path): ?MediaBackup
    {
        return $this->manager->find(MediaBackup::class, $path);
    }

    public function findByMediaId(string $mediaId): ?MediaBackup
    {
        return $this->manager->getRepository(MediaBackup::class)->findOneBy(['mediaId' => $mediaId]);
    }

    public function save(MediaBackup $backup): void
    {
        $this->manager->persist($backup);
        $this->manager->flush();
    }

    public function remove(MediaBackup $backup): void
    {
        $this->manager->remove($backup);
        $this->manager->flush();
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function findExistingPaths(array $paths): array
    {
        if ([] === $paths) {
            return [];
        }

        $existing = $this->manager->createQueryBuilder()
            ->select('b.path')
            ->from(MediaBackup::class, 'b')
            ->where('b.path IN (:paths)')
            ->setParameter('paths', $paths)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        return array_values(array_map(strval(...), $existing));
    }

    /**
     * @return list<MediaBackup>
     */
    public function findRemovedBefore(\DateTimeImmutable $limit, int $max): array
    {
        return (array) $this->manager->createQueryBuilder()
            ->select('b')
            ->from(MediaBackup::class, 'b')
            ->where('b.removedAt IS NOT NULL')
            ->andWhere('b.removedAt <= :limit')
            ->setParameter('limit', $limit)
            ->orderBy('b.removedAt', 'ASC')
            ->addOrderBy('b.path', 'ASC')
            ->setMaxResults(max(1, $max))
            ->getQuery()
            ->getResult();
    }
}
