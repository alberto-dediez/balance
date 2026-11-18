<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Repository;

use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\AssistantUsageRowBuilder;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageRole;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Security\Domain\Model\User\UserId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.assistant_conversation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineConversationRepository implements ConversationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function find(ConversationId $id): ?Conversation
    {
        return $this->manager->find(Conversation::class, $id);
    }

    public function add(Conversation $conversation): void
    {
        $this->manager->persist($conversation);

        try {
            $this->manager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConversationBusyException();
        }
    }

    public function messageStatusOf(MessageId $id): ?MessageStatus
    {
        $status = $this->manager->createQueryBuilder()
            ->select('m.status')
            ->from(Message::class, 'm')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;

        if ($status instanceof MessageStatus) {
            return $status;
        }

        return is_string($status) ? MessageStatus::tryFrom($status) : null;
    }

    public function remove(Conversation $conversation): void
    {
        $this->manager->remove($conversation);
        $this->manager->flush();
    }

    public function findByFilter(ConversationFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        /** @var list<Conversation> $result */
        $result = $query->getResult();

        return $result;
    }

    public function totalResultsByFilter(ConversationFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT c.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string
    {
        $cost = $this->manager->createQueryBuilder()
            ->select('COALESCE(SUM(m.usage.costUsd), 0)')
            ->from(Message::class, 'm')
            ->join('m.conversation', 'c')
            ->andWhere('c.userId = :userId')
            ->andWhere('m.createdAt >= :since')
            ->setParameter('userId', $userId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return AssistantUsage::formatCost(is_numeric($cost) ? (float) $cost : 0.0);
    }

    public function usageRowsBetween(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->manager->createQueryBuilder()
            ->select('m.createdAt AS createdAt', 'c.userId AS userId', 'm.usage.model AS model', 'm.usage.inputTokens AS inputTokens', 'm.usage.outputTokens AS outputTokens', 'm.usage.costUsd AS costUsd')
            ->from(Message::class, 'm')
            ->join('m.conversation', 'c')
            ->andWhere('m.role = :role')
            ->setParameter('role', MessageRole::Assistant->value)
        ;

        if ($from instanceof \DateTimeImmutable) {
            $qb->andWhere('m.createdAt >= :from')->setParameter('from', $from);
        }

        if ($to instanceof \DateTimeImmutable) {
            $qb->andWhere('m.createdAt < :to')->setParameter('to', $to);
        }

        /** @var list<array{createdAt: \DateTimeImmutable, userId: ?UserId, model: ?string, inputTokens: int, outputTokens: int, costUsd: numeric-string}> $rows */
        $rows = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $builder = new AssistantUsageRowBuilder();

        foreach ($rows as $row) {
            $builder->add($row['createdAt'], $row['userId'], $row['model'], (int) $row['inputTokens'], (int) $row['outputTokens'], (string) $row['costUsd']);
        }

        return $builder->rows();
    }

    private function createQueryBuilderFromFilter(ConversationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder()
            ->select('c')
            ->from(Conversation::class, 'c')
        ;

        if ($filter->userId instanceof UserId) {
            $qb->andWhere('c.userId = :userId')->setParameter('userId', $filter->userId);
        }

        if ($filter->lastActivityBefore instanceof \DateTimeImmutable) {
            $qb->andWhere('c.lastActivityAt < :lastActivityBefore')->setParameter('lastActivityBefore', $filter->lastActivityBefore);
        }

        if ($filter->pendingMessageBefore instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(sprintf('EXISTS (SELECT pm.id FROM %s pm WHERE pm.conversation = c AND pm.status = :pendingStatus AND pm.createdAt < :pendingMessageBefore)', Message::class))
                ->setParameter('pendingStatus', MessageStatus::Pending->value)
                ->setParameter('pendingMessageBefore', $filter->pendingMessageBefore)
            ;
        }

        $qb->orderBy('c.lastActivityAt', 'ASC' === $filter->orderWay ? 'ASC' : 'DESC');

        return $qb;
    }
}
