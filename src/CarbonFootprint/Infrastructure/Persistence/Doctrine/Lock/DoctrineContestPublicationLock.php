<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Lock;

use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContestPublicationLock implements ContestPublicationLock
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function acquire(AccountId $accountId): void
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('a')
            ->from(Account::class, 'a')
            ->where($qb->expr()->eq('a.id', ':id'))
            ->setParameter('id', (string) $accountId)
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult()
        ;
    }
}
