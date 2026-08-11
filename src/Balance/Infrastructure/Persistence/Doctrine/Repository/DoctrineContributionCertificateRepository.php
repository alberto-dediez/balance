<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateFilter;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.contribution_certificate', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContributionCertificateRepository implements ContributionCertificateRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ContributionCertificate $contributionCertificate): void
    {
        $this->manager->persist($contributionCertificate);
        $this->manager->flush();
    }

    public function find(ContributionCertificateId $contributionCertificateId): ?ContributionCertificate
    {
        $qb = $this->createQueryBuilderFromFilter(new ContributionCertificateFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cc.id', ':id'))
            ->setParameter('id', (string) $contributionCertificateId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByCode(string $code): ?ContributionCertificate
    {
        $qb = $this->createQueryBuilderFromFilter(new ContributionCertificateFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cc.code', ':code'))
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByVerificationToken(string $verificationToken): ?ContributionCertificate
    {
        $qb = $this->createQueryBuilderFromFilter(new ContributionCertificateFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cc.verificationToken', ':verificationToken'))
            ->setParameter('verificationToken', $verificationToken)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByProjectContributionId(ProjectContributionId $projectContributionId): ?ContributionCertificate
    {
        $qb = $this->createQueryBuilderFromFilter(new ContributionCertificateFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cc.projectContributionId', ':projectContributionId'))
            ->setParameter('projectContributionId', (string) $projectContributionId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ContributionCertificateFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('cc')
            ->from(ContributionCertificate::class, 'cc')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('cc.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->projectId) {
            $qb
                ->andWhere($qb->expr()->eq('cc.projectId', ':projectId'))
                ->setParameter('projectId', (string) $filter->projectId)
            ;
        }

        if (null !== $filter->type) {
            $qb
                ->andWhere($qb->expr()->eq('cc.type', ':type'))
                ->setParameter('type', $filter->type->value)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('cc.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->code) {
            $qb
                ->andWhere($qb->expr()->eq('cc.code', ':code'))
                ->setParameter('code', $filter->code)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'issuedAt' => $qb->orderBy('cc.issuedAt', $way),
            'code' => $qb->orderBy('cc.code', $way),
            default => $qb->orderBy('cc.issuedAt', $way),
        };

        $qb->addOrderBy('cc.id', 'ASC');

        return $qb;
    }

    /** @return array<ContributionCertificate> */
    public function findByFilter(ContributionCertificateFilter $filter): array
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

    public function totalResultsByFilter(ContributionCertificateFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT cc)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
