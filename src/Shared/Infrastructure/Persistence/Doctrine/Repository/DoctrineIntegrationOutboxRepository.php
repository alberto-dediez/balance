<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationOutboxStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.integration_outbox', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineIntegrationOutboxRepository implements IntegrationOutboxRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(IntegrationOutboxMessage $message): void
    {
        $this->manager->persist($message);
        $this->manager->flush();
    }

    public function save(IntegrationOutboxMessage $message): void
    {
        $this->manager->persist($message);
        $this->manager->flush();
    }

    /** @return list<IntegrationOutboxMessage> */
    public function pending(int $limit): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb
            ->select('o')
            ->from(IntegrationOutboxMessage::class, 'o')
            ->where($qb->expr()->isNull('o.sentAt'))
            ->orderBy('o.sequence', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function oldestPendingOccurredOn(): ?\DateTimeImmutable
    {
        $qb = $this->manager->createQueryBuilder();

        $oldest = $qb
            ->select('MIN(o.occurredOn)')
            ->from(IntegrationOutboxMessage::class, 'o')
            ->where($qb->expr()->isNull('o.sentAt'))
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (!is_string($oldest) || '' === $oldest) {
            return null;
        }

        return new \DateTimeImmutable($oldest);
    }

    /** @return list<IntegrationOutboxMessage> */
    public function list(IntegrationOutboxFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function purgeSentBefore(\DateTimeImmutable $limit): int
    {
        $qb = $this->manager->createQueryBuilder();

        return (int) $qb
            ->delete(IntegrationOutboxMessage::class, 'o')
            ->where($qb->expr()->isNotNull('o.sentAt'))
            ->andWhere($qb->expr()->lt('o.sentAt', ':limit'))
            ->setParameter('limit', $limit, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute()
        ;
    }

    private function createQueryBuilderFromFilter(IntegrationOutboxFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('o')
            ->from(IntegrationOutboxMessage::class, 'o')
        ;

        if (IntegrationOutboxStatus::Pending === $filter->status) {
            $qb->andWhere($qb->expr()->isNull('o.sentAt'));
        }

        if (IntegrationOutboxStatus::Sent === $filter->status) {
            $qb->andWhere($qb->expr()->isNotNull('o.sentAt'));
        }

        if (null !== $filter->type && '' !== $filter->type) {
            $qb
                ->andWhere($qb->expr()->eq('o.type', ':type'))
                ->setParameter('type', $filter->type)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'occurredOn' => $qb->orderBy('o.occurredOn', $way),
            default => $qb->orderBy('o.sequence', $way),
        };

        return $qb;
    }
}
