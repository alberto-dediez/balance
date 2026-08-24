<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseLeg;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.attendee_survey_response', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAttendeeSurveyResponseRepository implements AttendeeSurveyResponseRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(AttendeeSurveyResponse $attendeeSurveyResponse): void
    {
        $this->manager->persist($attendeeSurveyResponse);
        $this->manager->flush();
    }

    public function find(AttendeeSurveyResponseId $attendeeSurveyResponseId): ?AttendeeSurveyResponse
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('a')
            ->from(AttendeeSurveyResponse::class, 'a')
            ->where($qb->expr()->eq('a.id', ':id'))
            ->setParameter('id', $attendeeSurveyResponseId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<AttendeeSurveyResponse> */
    public function findByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb
            ->select('a', 'l')
            ->from(AttendeeSurveyResponse::class, 'a')
            ->leftJoin('a.legs', 'l')
            ->where($qb->expr()->eq('a.attendeeSurveyId', ':attendeeSurveyId'))
            ->setParameter('attendeeSurveyId', (string) $attendeeSurveyId)
            ->orderBy('a.registeredAt', 'ASC')
            ->addOrderBy('l.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): int
    {
        $qb = $this->manager->createQueryBuilder();

        return (int) $qb
            ->select('COUNT(a.id)')
            ->from(AttendeeSurveyResponse::class, 'a')
            ->where($qb->expr()->eq('a.attendeeSurveyId', ':attendeeSurveyId'))
            ->setParameter('attendeeSurveyId', (string) $attendeeSurveyId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @param list<AttendeeSurveyId> $attendeeSurveyIds */
    public function countByAttendeeSurveyIds(array $attendeeSurveyIds): int
    {
        if ([] === $attendeeSurveyIds) {
            return 0;
        }

        $qb = $this->manager->createQueryBuilder();

        return (int) $qb
            ->select('COUNT(a.id)')
            ->from(AttendeeSurveyResponse::class, 'a')
            ->where($qb->expr()->in('a.attendeeSurveyId', ':attendeeSurveyIds'))
            ->setParameter('attendeeSurveyIds', array_map(static fn(AttendeeSurveyId $attendeeSurveyId): string => (string) $attendeeSurveyId, $attendeeSurveyIds))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return array<string, int> */
    public function countLegsByDistanceSource(AttendeeSurveyId $attendeeSurveyId): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<array{source: DistanceSource|string, total: int|string}> $rows */
        $rows = $qb
            ->select('l.distanceSource AS source', 'COUNT(l.id) AS total')
            ->from(AttendeeSurveyResponseLeg::class, 'l')
            ->innerJoin('l.response', 'a')
            ->where($qb->expr()->eq('a.attendeeSurveyId', ':attendeeSurveyId'))
            ->setParameter('attendeeSurveyId', (string) $attendeeSurveyId)
            ->groupBy('l.distanceSource')
            ->getQuery()
            ->getScalarResult()
        ;

        $counts = [];

        foreach ($rows as $row) {
            $source = $row['source'] instanceof DistanceSource ? $row['source']->value : (string) $row['source'];
            $counts[$source] = (int) $row['total'];
        }

        return $counts;
    }

    public function countMultimodalResponses(AttendeeSurveyId $attendeeSurveyId): int
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<array{responseId: string}> $rows */
        $rows = $qb
            ->select('IDENTITY(l.response) AS responseId')
            ->from(AttendeeSurveyResponseLeg::class, 'l')
            ->innerJoin('l.response', 'a')
            ->where($qb->expr()->eq('a.attendeeSurveyId', ':attendeeSurveyId'))
            ->andWhere($qb->expr()->in('l.kind', ':kinds'))
            ->setParameter('attendeeSurveyId', (string) $attendeeSurveyId)
            ->setParameter('kinds', [LegKind::Intercity->value, LegKind::City->value])
            ->groupBy('l.response', 'l.kind')
            ->having($qb->expr()->gt('COUNT(l.id)', 1))
            ->getQuery()
            ->getScalarResult()
        ;

        $responseIds = [];

        foreach ($rows as $row) {
            $responseIds[(string) $row['responseId']] = true;
        }

        return count($responseIds);
    }
}
