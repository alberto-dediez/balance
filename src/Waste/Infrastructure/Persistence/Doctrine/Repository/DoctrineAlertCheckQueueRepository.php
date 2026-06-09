<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueue;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.alert_check_queue', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAlertCheckQueueRepository implements AlertCheckQueueRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(AlertCheckQueue $entry): void
    {
        $this->manager->persist($entry);
        $this->manager->flush();
    }

    public function find(AlertCheckQueueId $id): ?AlertCheckQueue
    {
        return $this->manager->find(AlertCheckQueue::class, $id);
    }

    public function existsFor(AccountId $accountId, \DateTimeImmutable $checkDate): bool
    {
        $result = $this->manager->createQueryBuilder()
            ->select('COUNT(q)')
            ->from(AlertCheckQueue::class, 'q')
            ->andWhere('q.accountId = :accountId')
            ->andWhere('q.checkDate = :checkDate')
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('checkDate', $checkDate->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result > 0;
    }

    /** @return array<AlertCheckQueue> */
    public function findAll(): array
    {
        return (array) $this->manager->createQueryBuilder()
            ->select('q')
            ->from(AlertCheckQueue::class, 'q')
            ->orderBy('q.checkDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function remove(AlertCheckQueue $entry): void
    {
        $this->manager->remove($entry);
        $this->manager->flush();
    }
}
