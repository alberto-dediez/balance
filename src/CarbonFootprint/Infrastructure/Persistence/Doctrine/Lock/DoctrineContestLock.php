<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Lock;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContestLock implements ContestLock
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function acquire(ContestId $contestId): void
    {
        $qb = $this->manager->createQueryBuilder();

        $contest = $qb
            ->select('c')
            ->from(Contest::class, 'c')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', (string) $contestId)
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult()
        ;

        if ($contest instanceof Contest) {
            $this->manager->refresh($contest);
        }
    }
}
