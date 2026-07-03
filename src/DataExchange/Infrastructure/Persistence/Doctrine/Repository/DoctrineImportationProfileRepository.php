<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Doctrine\Repository;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.importation_profile', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineImportationProfileRepository implements ImportationProfileRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(ImportationProfile $importationProfile): void
    {
        $this->manager->persist($importationProfile);
        $this->manager->flush();
    }

    public function find(ImportationProfileId $importationProfileId): ?ImportationProfile
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(ImportationProfile::class, 'p')
        ;

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id', ':id'),
            )
            ->setParameter('id', $importationProfileId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByTypeAndAccount(string $type, ?AccountId $accountId): ?ImportationProfile
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(ImportationProfile::class, 'p')
            ->andWhere(
                $qb->expr()->eq('p.type', ':type'),
            )
            ->setParameter('type', $type)
        ;

        if (null === $accountId) {
            $qb->andWhere($qb->expr()->isNull('p.accountId'));
        } else {
            $qb->andWhere($qb->expr()->eq('p.accountId', ':accountId'))
                ->setParameter('accountId', $accountId)
            ;
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function remove(ImportationProfile $importationProfile): void
    {
        $this->manager->remove($importationProfile);
        $this->manager->flush();
    }
}
