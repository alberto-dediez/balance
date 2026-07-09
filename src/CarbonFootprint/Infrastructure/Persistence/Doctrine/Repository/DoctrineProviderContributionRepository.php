<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.provider_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProviderContributionRepository implements ProviderContributionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(ProviderContribution $providerContribution): void
    {
        $this->manager->persist($providerContribution);
        $this->manager->flush();
    }

    public function find(ProviderContributionId $providerContributionId): ?ProviderContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(ProviderContribution::class, 'p')
            ->where($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', $providerContributionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?ProviderContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(ProviderContribution::class, 'p')
            ->where($qb->expr()->eq('p.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<ProviderContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb
            ->select('p')
            ->from(ProviderContribution::class, 'p')
            ->where($qb->expr()->eq('p.eventCarbonFootprintId', ':eventCarbonFootprintId'))
            ->setParameter('eventCarbonFootprintId', (string) $eventCarbonFootprintId)
            ->orderBy('p.registeredAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
