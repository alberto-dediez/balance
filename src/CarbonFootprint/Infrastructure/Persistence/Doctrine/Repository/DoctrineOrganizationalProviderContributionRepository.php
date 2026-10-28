<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionFilter;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.organizational_provider_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineOrganizationalProviderContributionRepository implements OrganizationalProviderContributionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(OrganizationalProviderContribution $contribution): void
    {
        $this->manager->persist($contribution);
        $this->manager->flush();
    }

    public function find(OrganizationalProviderContributionId $contributionId): ?OrganizationalProviderContribution
    {
        $qb = $this->createQueryBuilderFromFilter(new OrganizationalProviderContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', (string) $contributionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalProviderContribution
    {
        if ('' === $token) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new OrganizationalProviderContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('c.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<OrganizationalProviderContribution> */
    public function findActiveByFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        return array_values($this->findByFilter(new OrganizationalProviderContributionFilter(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            limit: 0,
        )));
    }

    public function findActiveByFootprintAndProvider(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ProviderId $providerId,
    ): ?OrganizationalProviderContribution {
        $qb = $this->createQueryBuilderFromFilter(new OrganizationalProviderContributionFilter(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
        ));

        return $qb
            ->andWhere($qb->expr()->eq('c.providerId', ':providerId'))
            ->setParameter('providerId', (string) $providerId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<OrganizationalProviderContribution> */
    public function findByFilter(OrganizationalProviderContributionFilter $filter): array
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

    public function totalResultsByFilter(OrganizationalProviderContributionFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(OrganizationalProviderContributionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(OrganizationalProviderContribution::class, 'c')
            ->where($qb->expr()->eq('c.removed', $qb->expr()->literal(false)))
        ;

        if ($filter->subjectType instanceof CarbonFootprintReportSubjectType) {
            $qb
                ->andWhere($qb->expr()->eq('c.subjectType', ':subjectType'))
                ->setParameter('subjectType', $filter->subjectType->value)
            ;
        }

        if (null !== $filter->carbonFootprintId) {
            $qb
                ->andWhere($qb->expr()->eq('c.carbonFootprintId', ':carbonFootprintId'))
                ->setParameter('carbonFootprintId', $filter->carbonFootprintId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('c.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->status instanceof ProviderContributionStatus) {
            $qb
                ->andWhere($qb->expr()->eq('c.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if ($filter->mode instanceof ProviderContributionMode) {
            $qb
                ->andWhere($qb->expr()->eq('c.mode', ':mode'))
                ->setParameter('mode', $filter->mode->value)
            ;
        }

        $way = 'DESC' === $filter->orderWay ? 'DESC' : 'ASC';
        $qb->orderBy('c.registeredAt', $way)->addOrderBy('c.id', 'ASC');

        return $qb;
    }
}
