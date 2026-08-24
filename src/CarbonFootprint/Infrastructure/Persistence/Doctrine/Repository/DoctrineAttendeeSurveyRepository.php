<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyFilter;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.attendee_survey', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAttendeeSurveyRepository implements AttendeeSurveyRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(AttendeeSurvey $attendeeSurvey): void
    {
        $this->manager->persist($attendeeSurvey);
        $this->manager->flush();
    }

    public function find(AttendeeSurveyId $attendeeSurveyId): ?AttendeeSurvey
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('a')
            ->from(AttendeeSurvey::class, 'a')
            ->where($qb->expr()->eq('a.id', ':id'))
            ->setParameter('id', $attendeeSurveyId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEventId(EventId $eventId): ?AttendeeSurvey
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('a')
            ->from(AttendeeSurvey::class, 'a')
            ->where($qb->expr()->eq('a.eventId', ':eventId'))
            ->setParameter('eventId', (string) $eventId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<AttendeeSurvey> */
    public function findByFilter(AttendeeSurveyFilter $filter): array
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

    public function totalResultsByFilter(AttendeeSurveyFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(a.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?AttendeeSurvey
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('a')
            ->from(AttendeeSurvey::class, 'a')
            ->where($qb->expr()->eq('a.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AttendeeSurveyFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('a')
            ->from(AttendeeSurvey::class, 'a')
            ->orderBy('a.registeredAt', 'DESC')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('a.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->eventId) {
            $qb
                ->andWhere($qb->expr()->eq('a.eventId', ':eventId'))
                ->setParameter('eventId', (string) $filter->eventId)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('a.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        return $qb;
    }
}
