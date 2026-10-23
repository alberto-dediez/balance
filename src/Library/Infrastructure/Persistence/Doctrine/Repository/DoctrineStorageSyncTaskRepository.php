<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Domain\Model\StorageSync\StorageSyncTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineStorageSyncTaskRepository implements StorageSyncTaskRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function find(string $path): ?StorageSyncTask
    {
        return $this->manager->find(StorageSyncTask::class, $path);
    }

    public function save(StorageSyncTask $task): void
    {
        $this->manager->persist($task);
        $this->manager->flush();
    }

    public function remove(StorageSyncTask $task): void
    {
        $this->manager->remove($task);
        $this->manager->flush();
    }

    /**
     * @return list<StorageSyncTask>
     */
    public function findPending(int $limit): array
    {
        $query = $this->manager->createQueryBuilder()
            ->select('t')
            ->from(StorageSyncTask::class, 't')
            ->orderBy('t.registeredAt', 'ASC')
            ->addOrderBy('t.path', 'ASC')
            ->getQuery()
        ;

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        return (array) $query->getResult();
    }

    public function countPending(): int
    {
        $count = $this->manager->createQueryBuilder()
            ->select('COUNT(t.path)')
            ->from(StorageSyncTask::class, 't')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
