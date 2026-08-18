<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.ledger_entry', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineLedgerEntryRepository implements LedgerEntryRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(LedgerEntry $ledgerEntry): void
    {
        $this->manager->persist($ledgerEntry);
        $this->manager->flush();
    }

    public function find(LedgerEntryId $ledgerEntryId): ?LedgerEntry
    {
        $qb = $this->createQueryBuilderFromFilter(new LedgerEntryFilter());

        return $qb
            ->andWhere($qb->expr()->eq('le.id', ':id'))
            ->setParameter('id', (string) $ledgerEntryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(LedgerEntryFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('le')
            ->from(LedgerEntry::class, 'le')
        ;

        if (null !== $filter->type) {
            $qb
                ->andWhere($qb->expr()->eq('le.type', ':type'))
                ->setParameter('type', $filter->type->value)
            ;
        }

        if (null !== $filter->types && [] !== $filter->types) {
            $qb
                ->andWhere($qb->expr()->in('le.type', ':types'))
                ->setParameter('types', array_map(static fn($type): string => $type->value, $filter->types))
            ;
        }

        if (null !== $filter->beneficiary) {
            $qb
                ->andWhere($qb->expr()->eq('le.beneficiary', ':beneficiary'))
                ->setParameter('beneficiary', $filter->beneficiary->value)
            ;
        }

        if (null !== $filter->beneficiaryId) {
            $qb
                ->andWhere($qb->expr()->eq('le.beneficiaryId', ':beneficiaryId'))
                ->setParameter('beneficiaryId', $filter->beneficiaryId)
            ;
        }

        if (null !== $filter->projectContributionId) {
            $qb
                ->andWhere($qb->expr()->eq('le.projectContributionId', ':projectContributionId'))
                ->setParameter('projectContributionId', (string) $filter->projectContributionId)
            ;
        }

        if (null !== $filter->projectId) {
            $qb
                ->andWhere($qb->expr()->eq('le.projectId', ':projectId'))
                ->setParameter('projectId', (string) $filter->projectId)
            ;
        }

        if (null !== $filter->creditPurchaseId) {
            $qb
                ->andWhere($qb->expr()->eq('le.creditPurchaseId', ':creditPurchaseId'))
                ->setParameter('creditPurchaseId', (string) $filter->creditPurchaseId)
            ;
        }

        if (null !== $filter->payoutId) {
            $qb
                ->andWhere($qb->expr()->eq('le.payoutId', ':payoutId'))
                ->setParameter('payoutId', (string) $filter->payoutId)
            ;
        }

        if (null !== $filter->settled) {
            $qb->andWhere($filter->settled ? $qb->expr()->isNotNull('le.payoutId') : $qb->expr()->isNull('le.payoutId'));
        }

        if ($filter->from instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->gte('le.occurredAt', ':from'))
                ->setParameter('from', $filter->from)
            ;
        }

        if ($filter->to instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->lte('le.occurredAt', ':to'))
                ->setParameter('to', $filter->to)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'amount' => $qb->orderBy('le.amount', $way),
            'settledAt' => $qb->orderBy('le.settledAt', $way),
            default => $qb->orderBy('le.occurredAt', $way),
        };

        $qb->addOrderBy('le.id', 'ASC');

        return $qb;
    }

    /** @return array<LedgerEntry> */
    public function findByFilter(LedgerEntryFilter $filter): array
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

    public function totalResultsByFilter(LedgerEntryFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT le)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function balanceFor(LedgerBeneficiary $beneficiary, ?string $beneficiaryId): LedgerBalance
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('SUM(le.amount) as accrued, COUNT(le) as entriesCount')
            ->addSelect('SUM(CASE WHEN le.payoutId IS NULL THEN 0 ELSE le.amount END) as settled')
            ->from(LedgerEntry::class, 'le')
            ->andWhere($qb->expr()->eq('le.beneficiary', ':beneficiary'))
            ->setParameter('beneficiary', $beneficiary->value)
        ;

        if (null === $beneficiaryId) {
            $qb->andWhere($qb->expr()->isNull('le.beneficiaryId'));
        } else {
            $qb
                ->andWhere($qb->expr()->eq('le.beneficiaryId', ':beneficiaryId'))
                ->setParameter('beneficiaryId', $beneficiaryId)
            ;
        }

        /** @var array{accrued: ?string, settled: ?string, entriesCount: int|string} $row */
        $row = $qb->getQuery()->getSingleResult();

        return new LedgerBalance(
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
            accruedAmount: round((float) $row['accrued'], 2),
            settledAmount: round((float) $row['settled'], 2),
            entriesCount: (int) $row['entriesCount'],
        );
    }

    /** @return array<string, LedgerBalance> */
    public function balancesByBeneficiary(LedgerBeneficiary $beneficiary): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<array{beneficiaryId: ?string, accrued: ?string, settled: ?string, entriesCount: int|string}> $rows */
        $rows = $qb
            ->select('le.beneficiaryId as beneficiaryId, SUM(le.amount) as accrued, COUNT(le) as entriesCount')
            ->addSelect('SUM(CASE WHEN le.payoutId IS NULL THEN 0 ELSE le.amount END) as settled')
            ->from(LedgerEntry::class, 'le')
            ->andWhere($qb->expr()->eq('le.beneficiary', ':beneficiary'))
            ->groupBy('le.beneficiaryId')
            ->setParameter('beneficiary', $beneficiary->value)
            ->getQuery()
            ->getResult()
        ;

        $balances = [];

        foreach ($rows as $row) {
            $balances[(string) $row['beneficiaryId']] = new LedgerBalance(
                beneficiary: $beneficiary,
                beneficiaryId: $row['beneficiaryId'],
                accruedAmount: round((float) $row['accrued'], 2),
                settledAmount: round((float) $row['settled'], 2),
                entriesCount: (int) $row['entriesCount'],
            );
        }

        return $balances;
    }

    /** @return list<LedgerEntry> */
    public function findSettleable(LedgerBeneficiary $beneficiary, string $beneficiaryId, ?\DateTimeImmutable $until): array
    {
        if (!$beneficiary->isSettleable()) {
            return [];
        }

        $entries = $this->findByFilter(new LedgerEntryFilter(
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
            settled: false,
            to: $until,
            limit: 0,
        ));

        return array_values(array_filter(
            $entries,
            static fn(LedgerEntry $entry): bool => $entry->isSettleable(),
        ));
    }

    /** @return list<LedgerEntry> */
    public function findByPayout(PayoutId $payoutId): array
    {
        return array_values($this->findByFilter(new LedgerEntryFilter(payoutId: $payoutId, limit: 0)));
    }

    public function recordedAmountFor(ProjectContributionId $projectContributionId): float
    {
        $qb = $this->manager->createQueryBuilder();

        $amount = $qb
            ->select('SUM(le.amount)')
            ->from(LedgerEntry::class, 'le')
            ->andWhere($qb->expr()->eq('le.projectContributionId', ':projectContributionId'))
            ->setParameter('projectContributionId', (string) $projectContributionId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return round((float) $amount, 2);
    }

    /** @return array<string, float> */
    public function totalsByType(LedgerEntryFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        /** @var list<array{type: LedgerEntryType|string, total: ?string}> $rows */
        $rows = $qb
            ->select('le.type as type, SUM(le.amount) as total')
            ->resetDQLPart('orderBy')
            ->groupBy('le.type')
            ->getQuery()
            ->getResult()
        ;

        $totals = [];

        foreach ($rows as $row) {
            $type = $row['type'] instanceof LedgerEntryType ? $row['type']->value : $row['type'];

            $totals[$type] = round((float) $row['total'], 2);
        }

        return $totals;
    }
}
