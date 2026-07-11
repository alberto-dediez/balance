<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
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
            ->select('a')
            ->from(AttendeeSurveyResponse::class, 'a')
            ->where($qb->expr()->eq('a.attendeeSurveyId', ':attendeeSurveyId'))
            ->setParameter('attendeeSurveyId', (string) $attendeeSurveyId)
            ->orderBy('a.registeredAt', 'ASC')
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
}
