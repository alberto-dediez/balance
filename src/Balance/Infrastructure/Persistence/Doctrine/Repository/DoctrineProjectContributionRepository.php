<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.project_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProjectContributionRepository implements ProjectContributionRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ProjectContribution $projectContribution): void
    {
        $this->manager->persist($projectContribution);
        $this->manager->flush();
    }

    public function find(ProjectContributionId $projectContributionId): ?ProjectContribution
    {
        $qb = $this->createQueryBuilderFromFilter(new ProjectContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('pc.id', ':id'))
            ->setParameter('id', (string) $projectContributionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByPaymentId(PaymentId $paymentId): ?ProjectContribution
    {
        $qb = $this->createQueryBuilderFromFilter(new ProjectContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('pc.paymentId', ':paymentId'))
            ->setParameter('paymentId', (string) $paymentId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProjectContributionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('pc')
            ->from(ProjectContribution::class, 'pc')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('pc.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->projectId) {
            $qb
                ->andWhere($qb->expr()->eq('pc.projectId', ':projectId'))
                ->setParameter('projectId', (string) $filter->projectId)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('pc.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->origin) {
            $qb
                ->andWhere($qb->expr()->eq('pc.origin', ':origin'))
                ->setParameter('origin', $filter->origin->value)
            ;
        }

        if (null !== $filter->originId) {
            $qb
                ->andWhere($qb->expr()->eq('pc.originId', ':originId'))
                ->setParameter('originId', $filter->originId)
            ;
        }

        if (null !== $filter->paymentId) {
            $qb
                ->andWhere($qb->expr()->eq('pc.paymentId', ':paymentId'))
                ->setParameter('paymentId', (string) $filter->paymentId)
            ;
        }

        if ($filter->from instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->gte('pc.registeredAt', ':from'))
                ->setParameter('from', $filter->from)
            ;
        }

        if ($filter->to instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->lte('pc.registeredAt', ':to'))
                ->setParameter('to', $filter->to)
            ;
        }

        if ($filter->expiresBefore instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->lte('pc.expiresAt', ':expiresBefore'))
                ->setParameter('expiresBefore', $filter->expiresBefore)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('pc.registeredAt', $way),
            'totalAmount' => $qb->orderBy('pc.totalAmount', $way),
            'paidAt' => $qb->orderBy('pc.paidAt', $way),
            default => $qb->orderBy('pc.registeredAt', $way),
        };

        $qb->addOrderBy('pc.id', 'ASC');

        return $qb;
    }

    /** @return array<ProjectContribution> */
    public function findByFilter(ProjectContributionFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(ProjectContributionFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT pc)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function committedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float
    {
        return $this->committedTonnesByAccount($accountId, $now)[(string) $projectId] ?? 0.0;
    }

    /** @return array<string, float> */
    public function committedTonnesByAccount(AccountId $accountId, \DateTimeImmutable $now): array
    {
        $qb = $this->manager->createQueryBuilder();

        $rows = $qb
            ->select('pc.projectId as projectId, SUM(pc.tonnes) as tonnes')
            ->from(ProjectContribution::class, 'pc')
            ->andWhere($qb->expr()->eq('pc.accountId', ':accountId'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('pc.status', ':paid'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('pc.status', ':pending'),
                        $qb->expr()->gt('pc.expiresAt', ':now'),
                    ),
                ),
            )
            ->groupBy('pc.projectId')
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('paid', ProjectContributionStatus::Paid->value)
            ->setParameter('pending', ProjectContributionStatus::Pending->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        $committed = [];

        foreach ($rows as $row) {
            $committed[(string) $row['projectId']] = (float) $row['tonnes'];
        }

        return $committed;
    }

    public function reservedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float
    {
        $qb = $this->manager->createQueryBuilder();

        return (float) $qb
            ->select('COALESCE(SUM(pc.tonnes), 0)')
            ->from(ProjectContribution::class, 'pc')
            ->andWhere($qb->expr()->eq('pc.accountId', ':accountId'))
            ->andWhere($qb->expr()->eq('pc.projectId', ':projectId'))
            ->andWhere($qb->expr()->eq('pc.status', ':pending'))
            ->andWhere($qb->expr()->gt('pc.expiresAt', ':now'))
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('projectId', (string) $projectId)
            ->setParameter('pending', ProjectContributionStatus::Pending->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
