<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionTranslation;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.mitigation_action', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMitigationActionRepository implements MitigationActionRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MitigationAction $mitigationAction): void
    {
        $this->manager->persist($mitigationAction);
        $this->addTranslations($mitigationAction, MitigationActionTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MitigationAction $mitigationAction): void
    {
        $this->manager->remove($mitigationAction);
        $this->manager->flush();
    }

    public function find(MitigationActionId $mitigationActionId): ?MitigationAction
    {
        $qb = $this->createQueryBuilderFromFilter(new MitigationActionFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.id', ':id'),
            )
            ->setParameter('id', (string) $mitigationActionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MitigationActionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(MitigationAction::class, 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.removed'),
                    $qb->expr()->eq('a.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if ($filter->subjectType instanceof CarbonFootprintReportSubjectType) {
            $qb
                ->andWhere($qb->expr()->eq('a.subjectType', ':subjectType'))
                ->setParameter('subjectType', $filter->subjectType->value)
            ;
        }

        if (null !== $filter->carbonFootprintId) {
            $qb
                ->andWhere($qb->expr()->eq('a.carbonFootprintId', ':carbonFootprintId'))
                ->setParameter('carbonFootprintId', $filter->carbonFootprintId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('a.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'name' => $qb->orderBy('a.name', $way),
            default => $qb->orderBy('a.registeredAt', $way),
        };

        $qb->addOrderBy('a.id', 'ASC');

        return $qb;
    }

    /** @return array<MitigationAction> */
    public function findByFilter(MitigationActionFilter $filter): array
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

    public function totalResultsByFilter(MitigationActionFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
