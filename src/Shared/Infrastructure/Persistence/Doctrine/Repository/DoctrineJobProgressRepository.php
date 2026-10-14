<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\JobProgress\JobProgress;
use App\Shared\Domain\Model\JobProgress\JobProgressId;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.job_progress', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineJobProgressRepository implements JobProgressRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(JobProgress $jobProgress): void
    {
        $this->manager->persist($jobProgress);
        $this->manager->flush();
    }

    public function find(JobProgressId $id): ?JobProgress
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('j')
            ->from(JobProgress::class, 'j')
            ->where($qb->expr()->eq('j.id', ':id'))
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByKey(JobProgressKey $key): ?JobProgress
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('j')
            ->from(JobProgress::class, 'j')
            ->where($qb->expr()->eq('j.key', ':key'))
            ->setParameter('key', (string) $key)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
