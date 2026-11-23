<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Integration\IntegrationInboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationInboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.integration_inbox', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineIntegrationInboxRepository implements IntegrationInboxRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function has(string $messageId): bool
    {
        return null !== $this->find($messageId);
    }

    public function find(string $messageId): ?IntegrationInboxMessage
    {
        return $this->manager->find(IntegrationInboxMessage::class, $messageId);
    }

    public function add(IntegrationInboxMessage $message): void
    {
        $this->manager->persist($message);
        $this->manager->flush();
    }

    /** @return list<IntegrationInboxMessage> */
    public function list(IntegrationInboxFilter $filter): array
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

    public function purgeReceivedBefore(\DateTimeImmutable $limit): int
    {
        $qb = $this->manager->createQueryBuilder();

        return (int) $qb
            ->delete(IntegrationInboxMessage::class, 'i')
            ->where($qb->expr()->lt('i.receivedAt', ':limit'))
            ->setParameter('limit', $limit, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute()
        ;
    }

    private function createQueryBuilderFromFilter(IntegrationInboxFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('i')
            ->from(IntegrationInboxMessage::class, 'i')
        ;

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('i.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->source && '' !== $filter->source) {
            $qb
                ->andWhere($qb->expr()->eq('i.source', ':source'))
                ->setParameter('source', $filter->source)
            ;
        }

        if (null !== $filter->type && '' !== $filter->type) {
            $qb
                ->andWhere($qb->expr()->eq('i.type', ':type'))
                ->setParameter('type', $filter->type)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'sequence' => $qb->orderBy('i.sequence', $way),
            default => $qb->orderBy('i.receivedAt', $way),
        };

        return $qb;
    }
}
