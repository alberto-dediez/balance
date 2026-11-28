<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.contest_entry', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContestEntryRepository implements ContestEntryRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(ContestEntry $contestEntry): void
    {
        try {
            $this->manager->persist($contestEntry);
            $this->manager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ContestEntryAlreadyExistsException();
        }
    }

    public function remove(ContestEntry $contestEntry): void
    {
        $this->manager->remove($contestEntry);
        $this->manager->flush();
    }

    public function refresh(ContestEntry $contestEntry): void
    {
        $this->manager->refresh($contestEntry);
    }

    public function find(ContestEntryId $contestEntryId): ?ContestEntry
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.id', ':id'))
            ->setParameter('id', (string) $contestEntryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<ContestEntry> */
    public function findByFilter(ContestEntryFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        /** @var list<ContestEntry> $result */
        $result = $query->getResult();

        return $result;
    }

    public function countByFilter(ContestEntryFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findOneByOrigin(ContestId $contestId, ProjectContributionOrigin $origin, string $originId): ?ContestEntry
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.contestId', ':contestId'))
            ->andWhere($qb->expr()->eq('e.origin', ':origin'))
            ->andWhere($qb->expr()->eq('e.originId', ':originId'))
            ->setParameter('contestId', (string) $contestId)
            ->setParameter('origin', $origin->value)
            ->setParameter('originId', $originId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByEmail(ContestId $contestId, string $email): ?ContestEntry
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.contestId', ':contestId'))
            ->andWhere($qb->expr()->eq('e.email', ':email'))
            ->setParameter('contestId', (string) $contestId)
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<ContestEntry> */
    public function findByProjectContributionId(string $projectContributionId): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<ContestEntry> $result */
        $result = $qb
            ->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.projectContributionId', ':projectContributionId'))
            ->setParameter('projectContributionId', $projectContributionId)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /** @return list<ContestEntryId> */
    public function findEligibleWithoutPrizeIds(ContestId $contestId): array
    {
        $qb = $this->eligibleWithoutPrize($contestId);

        /** @var list<ContestEntry> $entries */
        $entries = $qb->getQuery()->getResult();

        return array_map(static fn(ContestEntry $entry): ContestEntryId => $entry->id(), $entries);
    }

    public function findNextEligibleWithoutPrize(ContestId $contestId): ?ContestEntry
    {
        return $this->eligibleWithoutPrize($contestId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countWinners(ContestId $contestId): int
    {
        $qb = $this->manager->createQueryBuilder();

        return (int) $qb
            ->select('COUNT(e.id)')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.contestId', ':contestId'))
            ->andWhere($qb->expr()->eq('e.winner', ':winner'))
            ->setParameter('contestId', (string) $contestId)
            ->setParameter('winner', true, Types::BOOLEAN)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function eligibleWithoutPrize(ContestId $contestId): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.contestId', ':contestId'))
            ->andWhere($qb->expr()->eq('e.status', ':status'))
            ->andWhere($qb->expr()->eq('e.winner', ':winner'))
            ->andWhere($qb->expr()->isNull('e.personalDataPurgedAt'))
            ->setParameter('contestId', (string) $contestId)
            ->setParameter('status', ContestEntryStatus::Confirmed->value)
            ->setParameter('winner', false, Types::BOOLEAN)
            ->orderBy('e.entryNumber', 'ASC')
        ;
    }

    private function createQueryBuilderFromFilter(ContestEntryFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(ContestEntry::class, 'e')
            ->where($qb->expr()->eq('e.contestId', ':contestId'))
            ->setParameter('contestId', (string) $filter->contestId)
        ;

        if ($filter->status instanceof ContestEntryStatus) {
            $qb
                ->andWhere($qb->expr()->eq('e.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->winner) {
            $qb
                ->andWhere($qb->expr()->eq('e.winner', ':winner'))
                ->setParameter('winner', $filter->winner, Types::BOOLEAN)
            ;
        }

        if (null !== $filter->text && '' !== trim($filter->text)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('e.name', ':text'),
                        $qb->expr()->like('e.email', ':text'),
                    ),
                )
                ->setParameter('text', '%' . trim($filter->text) . '%')
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'name' => $qb->orderBy('e.name', $way),
            'email' => $qb->orderBy('e.email', $way),
            'confirmedAt' => $qb->orderBy('e.confirmedAt', $way),
            default => $qb->orderBy('e.entryNumber', $way)->addOrderBy('e.registeredAt', $way),
        };

        return $qb;
    }
}
