<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.credit_purchase', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCreditPurchaseRepository implements CreditPurchaseRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(CreditPurchase $creditPurchase): void
    {
        $this->manager->persist($creditPurchase);
        $this->manager->flush();
    }

    public function find(CreditPurchaseId $creditPurchaseId): ?CreditPurchase
    {
        $qb = $this->createQueryBuilderFromFilter(new CreditPurchaseFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cp.id', ':id'))
            ->setParameter('id', (string) $creditPurchaseId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function existsByReference(string $reference, ?CreditPurchaseId $excluding = null): bool
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('COUNT(cp.id)')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.reference', ':reference'))
            ->setParameter('reference', $reference)
        ;

        if ($excluding instanceof CreditPurchaseId) {
            $qb
                ->andWhere($qb->expr()->neq('cp.id', ':excluding'))
                ->setParameter('excluding', (string) $excluding)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function nextLotNumberFor(string $reference): int
    {
        $qb = $this->manager->createQueryBuilder();

        $maxLotNumber = $qb
            ->select('MAX(cp.lotNumber)')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.reference', ':reference'))
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null !== $maxLotNumber ? (int) $maxLotNumber + 1 : 1;
    }

    private function createQueryBuilderFromFilter(CreditPurchaseFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('cp')
            ->from(CreditPurchase::class, 'cp')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('cp.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->projectId) {
            $qb
                ->andWhere($qb->expr()->eq('cp.projectId', ':projectId'))
                ->setParameter('projectId', (string) $filter->projectId)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('cp.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->paymentType) {
            $qb
                ->andWhere($qb->expr()->eq('cp.paymentType', ':paymentType'))
                ->setParameter('paymentType', $filter->paymentType->value)
            ;
        }

        if (!empty($filter->reference)) {
            $qb
                ->andWhere($qb->expr()->like('cp.reference', ':reference'))
                ->setParameter('reference', "%{$filter->reference}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'requestedAt' => $qb->orderBy('cp.requestedAt', $way),
            'resolvedAt' => $qb->orderBy('cp.resolvedAt', $way),
            default => $qb->orderBy('cp.requestedAt', $way),
        };

        $qb->addOrderBy('cp.id', 'ASC');

        return $qb;
    }

    /** @return array<CreditPurchase> */
    public function findByFilter(CreditPurchaseFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(CreditPurchaseFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT cp)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return array<CreditBalance> */
    public function balancesByAccount(AccountId $accountId): array
    {
        $qb = $this->manager->createQueryBuilder();

        $rows = $qb
            ->select('cp.projectId as projectId, SUM(cp.purchasedTonnes - cp.offsetTonnes - cp.upperOffsetTonnes - cp.voidedTonnes) as purchasedTonnes, COUNT(cp) as purchasesCount')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.accountId', ':accountId'))
            ->andWhere($qb->expr()->eq('cp.status', ':status'))
            ->groupBy('cp.projectId')
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('status', CreditPurchaseStatus::Validated->value)
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            static fn(array $row): CreditBalance => new CreditBalance(
                projectId: $row['projectId'],
                purchasedTonnes: (float) $row['purchasedTonnes'],
                consumedTonnes: 0.0,
                purchasesCount: (int) $row['purchasesCount'],
            ),
            $rows,
        );
    }

    public function balanceFor(AccountId $accountId, ProjectId $projectId): CreditBalance
    {
        foreach ($this->balancesByAccount($accountId) as $balance) {
            if ($balance->projectId()->equals($projectId)) {
                return $balance;
            }
        }

        return new CreditBalance(
            projectId: $projectId,
            purchasedTonnes: 0.0,
            consumedTonnes: 0.0,
            purchasesCount: 0,
        );
    }

    public function purchasedTonnesForProject(ProjectId $projectId): float
    {
        $qb = $this->manager->createQueryBuilder();

        return (float) $qb
            ->select('COALESCE(SUM(cp.purchasedTonnes - cp.offsetTonnes - cp.upperOffsetTonnes - cp.voidedTonnes), 0)')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.projectId', ':projectId'))
            ->andWhere($qb->expr()->eq('cp.status', ':status'))
            ->setParameter('projectId', (string) $projectId)
            ->setParameter('status', CreditPurchaseStatus::Validated->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function latestValidatedFor(AccountId $accountId, ProjectId $projectId): ?CreditPurchase
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<CreditPurchase> $purchases */
        $purchases = $qb
            ->select('cp')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.accountId', ':accountId'))
            ->andWhere($qb->expr()->eq('cp.projectId', ':projectId'))
            ->andWhere($qb->expr()->eq('cp.status', ':status'))
            ->orderBy('cp.resolvedAt', 'DESC')
            ->addOrderBy('cp.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('projectId', (string) $projectId)
            ->setParameter('status', CreditPurchaseStatus::Validated->value)
            ->getQuery()
            ->getResult()
        ;

        return $purchases[0] ?? null;
    }

    /** @return list<CreditPurchase> */
    public function latestValidatedPurchases(): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<CreditPurchase> $purchases */
        $purchases = $qb
            ->select('cp')
            ->from(CreditPurchase::class, 'cp')
            ->andWhere($qb->expr()->eq('cp.status', ':status'))
            ->orderBy('cp.resolvedAt', 'ASC')
            ->addOrderBy('cp.id', 'ASC')
            ->setParameter('status', CreditPurchaseStatus::Validated->value)
            ->getQuery()
            ->getResult()
        ;

        $latest = [];

        foreach ($purchases as $creditPurchase) {
            $accountId = $creditPurchase->accountId();
            $projectId = $creditPurchase->projectId();

            if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
                continue;
            }

            $latest[(string) $accountId . '|' . (string) $projectId] = $creditPurchase;
        }

        return array_values($latest);
    }
}
