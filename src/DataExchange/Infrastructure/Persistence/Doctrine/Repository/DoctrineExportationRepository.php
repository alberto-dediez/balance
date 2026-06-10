<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Doctrine\Repository;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.exportation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineExportationRepository implements ExportationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Exportation $exportation): void
    {
        try {
            $this->manager->persist($exportation);
            $this->manager->flush();
        } catch (\Throwable $exception) {
            throw new NotExportedException($exception->getMessage());
        }
    }

    public function remove(Exportation $exportation): void
    {
        $this->manager->remove($exportation);
        $this->manager->flush();
    }

    public function find(ExportationId $exportationId): ?Exportation
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Exportation::class, 'e')
        ;

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', $exportationId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return Exportation[] */
    public function findExpired(\DateTimeImmutable $now): array
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Exportation::class, 'e')
        ;

        return $qb
            ->andWhere(
                $qb->expr()->lt('e.validUntil', ':now'),
            )
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;
    }
}
