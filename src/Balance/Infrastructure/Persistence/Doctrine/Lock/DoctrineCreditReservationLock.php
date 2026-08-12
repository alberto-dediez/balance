<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Lock;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCreditReservationLock implements CreditReservationLock
{
    public function __construct(private EntityManagerInterface $manager) {}

    /**
     * @template T
     *
     * @param callable(): T $reservation
     *
     * @return T
     */
    public function withLock(AccountId $accountId, ProjectId $projectId, callable $reservation): mixed
    {
        return $this->manager->wrapInTransaction(
            function () use ($accountId, $projectId, $reservation): mixed {
                $this->lockAdhesion($accountId, $projectId);

                return $reservation();
            },
        );
    }

    private function lockAdhesion(AccountId $accountId, ProjectId $projectId): void
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('ap')
            ->from(AccountProject::class, 'ap')
            ->andWhere($qb->expr()->eq('ap.accountId', ':accountId'))
            ->andWhere($qb->expr()->eq('ap.projectId', ':projectId'))
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('projectId', (string) $projectId)
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult()
        ;
    }
}
