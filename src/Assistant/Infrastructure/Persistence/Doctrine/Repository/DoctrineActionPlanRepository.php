<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Repository;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\AssistantUsageRowBuilder;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.assistant_action_plan', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineActionPlanRepository implements ActionPlanRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function find(ActionPlanId $id): ?ActionPlan
    {
        return $this->manager->find(ActionPlan::class, $id);
    }

    public function add(ActionPlan $plan): void
    {
        $this->manager->persist($plan);

        try {
            $this->manager->flush();
        } catch (OptimisticLockException $exception) {
            throw new ActionPlanConcurrentlyModifiedException($plan->id(), $exception);
        }
    }

    public function statusOf(ActionPlanId $id): ?ActionPlanStatus
    {
        $status = $this->manager->createQueryBuilder()
            ->select('p.status')
            ->from(ActionPlan::class, 'p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;

        if ($status instanceof ActionPlanStatus) {
            return $status;
        }

        return is_string($status) ? ActionPlanStatus::tryFrom($status) : null;
    }

    public function remove(ActionPlan $plan): void
    {
        $this->manager->remove($plan);
        $this->manager->flush();
    }

    public function findByFilter(ActionPlanFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        /** @var list<ActionPlan> $result */
        $result = $query->getResult();

        return $result;
    }

    public function totalResultsByFilter(ActionPlanFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string
    {
        $cost = $this->manager->createQueryBuilder()
            ->select('COALESCE(SUM(p.usage.costUsd), 0)')
            ->from(ActionPlan::class, 'p')
            ->andWhere('p.userId = :userId')
            ->andWhere('COALESCE(p.finishedAt, p.startedAt, p.proposedAt) >= :since')
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
            ->select('p.proposedAt AS proposedAt', 'p.userId AS userId', 'p.usage.model AS model', 'p.usage.inputTokens AS inputTokens', 'p.usage.outputTokens AS outputTokens', 'p.usage.costUsd AS costUsd')
            ->from(ActionPlan::class, 'p')
        ;

        if ($from instanceof \DateTimeImmutable) {
            $qb->andWhere('p.proposedAt >= :from')->setParameter('from', $from);
        }

        if ($to instanceof \DateTimeImmutable) {
            $qb->andWhere('p.proposedAt < :to')->setParameter('to', $to);
        }

        /** @var list<array{proposedAt: \DateTimeImmutable, userId: ?UserId, model: ?string, inputTokens: int, outputTokens: int, costUsd: numeric-string}> $rows */
        $rows = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $builder = new AssistantUsageRowBuilder();

        foreach ($rows as $row) {
            $builder->add($row['proposedAt'], $row['userId'], $row['model'], (int) $row['inputTokens'], (int) $row['outputTokens'], (string) $row['costUsd']);
        }

        return $builder->rows();
    }

    private function createQueryBuilderFromFilter(ActionPlanFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder()
            ->select('p')
            ->from(ActionPlan::class, 'p')
        ;

        if ($filter->conversationId instanceof ConversationId) {
            $qb->andWhere('p.conversationId = :conversationId')->setParameter('conversationId', $filter->conversationId);
        }

        if ($filter->userId instanceof UserId) {
            $qb->andWhere('p.userId = :userId')->setParameter('userId', $filter->userId);
        }

        if (null !== $filter->statuses && [] !== $filter->statuses) {
            $qb->andWhere('p.status IN (:statuses)')->setParameter('statuses', array_map(static fn(ActionPlanStatus $status): string => $status->value, $filter->statuses));
        }

        if ($filter->proposedBefore instanceof \DateTimeImmutable) {
            $qb->andWhere('p.proposedAt < :proposedBefore')->setParameter('proposedBefore', $filter->proposedBefore);
        }

        if ($filter->lastProgressBefore instanceof \DateTimeImmutable) {
            $qb->andWhere('COALESCE(p.lastProgressAt, p.approvedAt) < :lastProgressBefore')->setParameter('lastProgressBefore', $filter->lastProgressBefore);
        }

        $qb->orderBy('p.proposedAt', 'ASC' === $filter->orderWay ? 'ASC' : 'DESC');

        return $qb;
    }
}
