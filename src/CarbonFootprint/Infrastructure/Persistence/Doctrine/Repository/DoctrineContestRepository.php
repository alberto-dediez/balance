<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestFilter;
use App\CarbonFootprint\Domain\Model\Contest\ContestHost;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\CarbonFootprint\Domain\Model\Contest\ContestTranslation;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.contest', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContestRepository implements ContestRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Contest $contest): void
    {
        $this->manager->persist($contest);
        $this->addTranslations($contest, ContestTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Contest $contest): void
    {
        $this->manager->remove($contest);
        $this->manager->flush();
    }

    public function find(ContestId $contestId): ?Contest
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(Contest::class, 'c')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', (string) $contestId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<Contest> */
    public function findByFilter(ContestFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        /** @var list<Contest> $result */
        $result = $query->getResult();

        return $result;
    }

    public function countByFilter(ContestFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT c)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findPublishedByHost(ContestHostReference $host): ?Contest
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(Contest::class, 'c')
            ->innerJoin(ContestHost::class, 'h', 'WITH', 'h.contest = c')
            ->where($qb->expr()->eq('c.status', ':status'))
            ->andWhere($qb->expr()->eq('h.hostType', ':hostType'))
            ->andWhere($qb->expr()->eq('h.hostId', ':hostId'))
            ->setParameter('status', ContestStatus::Published->value)
            ->setParameter('hostType', $host->hostType->value)
            ->setParameter('hostId', $host->hostId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<Contest> */
    public function findPublishedEndedBefore(\DateTimeImmutable $moment): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<Contest> $result */
        $result = $qb
            ->select('c')
            ->from(Contest::class, 'c')
            ->where($qb->expr()->eq('c.status', ':status'))
            ->andWhere($qb->expr()->lt('c.endsAt', ':moment'))
            ->setParameter('status', ContestStatus::Published->value)
            ->setParameter('moment', $moment, 'datetime_immutable')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    private function createQueryBuilderFromFilter(ContestFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Contest::class, 'c')
            ->leftJoin(ContestTranslation::class, 'trans', 'WITH', 'trans.translatable = c AND trans.locale = :locale')
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('c.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->status instanceof ContestStatus) {
            $qb
                ->andWhere($qb->expr()->eq('c.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if ($filter->participationMode instanceof ContestParticipationMode) {
            $qb
                ->andWhere($qb->expr()->eq('c.participationMode', ':participationMode'))
                ->setParameter('participationMode', $filter->participationMode->value)
            ;
        }

        if ($filter->awardMode instanceof ContestAwardMode) {
            $qb
                ->andWhere($qb->expr()->eq('c.awardMode', ':awardMode'))
                ->setParameter('awardMode', $filter->awardMode->value)
            ;
        }

        if (null !== $filter->text && '' !== trim($filter->text)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNull('trans.locale'),
                            $qb->expr()->like('c.name', ':text'),
                        ),
                        $qb->expr()->like('trans.name', ':text'),
                    ),
                )
                ->setParameter('text', '%' . trim($filter->text) . '%')
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'startsAt' => $qb->orderBy('c.startsAt', $way),
            'endsAt' => $qb->orderBy('c.endsAt', $way),
            'registeredAt' => $qb->orderBy('c.registeredAt', $way),
            default => $qb->orderBy('c.name', $way),
        };

        return $qb;
    }
}
