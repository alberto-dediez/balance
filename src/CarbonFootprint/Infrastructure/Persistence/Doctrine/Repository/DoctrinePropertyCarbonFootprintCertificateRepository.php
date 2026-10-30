<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.property_carbon_footprint_certificate', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePropertyCarbonFootprintCertificateRepository implements PropertyCarbonFootprintCertificateRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(PropertyCarbonFootprintCertificate $certificate): void
    {
        $this->manager->persist($certificate);
        $this->manager->flush();
    }

    public function remove(PropertyCarbonFootprintCertificate $certificate): void
    {
        $this->manager->remove($certificate);
        $this->manager->flush();
    }

    public function find(PropertyCarbonFootprintCertificateId $id): ?PropertyCarbonFootprintCertificate
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function isRevoked(PropertyCarbonFootprintCertificateId $id): bool
    {
        $qb = $this->manager->createQueryBuilder();

        $count = $qb
            ->select('COUNT(c.id)')
            ->from(PropertyCarbonFootprintCertificate::class, 'c')
            ->andWhere($qb->expr()->eq('c.id', ':id'))
            ->andWhere($qb->expr()->eq('c.status', ':revoked'))
            ->setParameter('id', (string) $id)
            ->setParameter('revoked', PropertyCarbonFootprintCertificateStatus::Revoked->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
    }

    public function findByCode(string $code): ?PropertyCarbonFootprintCertificate
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->eq('c.code', ':code'))
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByVerificationToken(string $verificationToken): ?PropertyCarbonFootprintCertificate
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->eq('c.verificationToken', ':verificationToken'))
            ->setParameter('verificationToken', $verificationToken)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findCurrentByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): ?PropertyCarbonFootprintCertificate
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->eq('c.propertyCarbonFootprintId', ':propertyCarbonFootprintId'))
            ->andWhere($qb->expr()->neq('c.status', ':revoked'))
            ->setParameter('propertyCarbonFootprintId', (string) $id)
            ->setParameter('revoked', PropertyCarbonFootprintCertificateStatus::Revoked->value)
            ->orderBy('c.issuedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): array
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->eq('c.propertyCarbonFootprintId', ':propertyCarbonFootprintId'))
            ->setParameter('propertyCarbonFootprintId', (string) $id)
            ->orderBy('c.issuedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->manager->createQueryBuilder()
            ->select('c')
            ->from(PropertyCarbonFootprintCertificate::class, 'c')
        ;
    }
}
