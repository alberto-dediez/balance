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

    public function findOneByReference(string $reference): ?CreditPurchase
    {
        $qb = $this->createQueryBuilderFromFilter(new CreditPurchaseFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cp.reference', ':reference'))
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getOneOrNullResult()
        ;
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
            ->select('cp.projectId as projectId, SUM(cp.purchasedTonnes) as purchasedTonnes, SUM(cp.amount) as amount, COUNT(cp) as purchasesCount')
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
                amount: (float) $row['amount'],
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
            amount: 0.0,
            purchasesCount: 0,
        );
    }
}
