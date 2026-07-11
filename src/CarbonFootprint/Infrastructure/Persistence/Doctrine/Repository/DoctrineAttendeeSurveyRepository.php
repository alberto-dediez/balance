<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use Doctrine\ORM\EntityManagerInterface;
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
}
