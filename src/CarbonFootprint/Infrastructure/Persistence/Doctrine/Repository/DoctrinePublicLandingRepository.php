<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingBlockTranslation;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingTranslation;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.public_landing', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePublicLandingRepository implements PublicLandingRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(PublicLanding $publicLanding): void
    {
        $this->manager->persist($publicLanding);
        $this->addTranslations($publicLanding, PublicLandingTranslation::class, $this->manager);

        foreach ($publicLanding->blocks() as $block) {
            $this->manager->persist($block);
            $this->addTranslations($block, PublicLandingBlockTranslation::class, $this->manager);
        }

        $this->manager->flush();
    }

    public function find(PublicLandingId $publicLandingId): ?PublicLanding
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('l')
            ->from(PublicLanding::class, 'l')
            ->where($qb->expr()->eq('l.id', ':id'))
            ->setParameter('id', $publicLandingId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByOwner(PublicLandingOwnerType $ownerType, string $ownerId): ?PublicLanding
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('l')
            ->from(PublicLanding::class, 'l')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.ownerType', ':ownerType'),
                    $qb->expr()->eq('l.ownerId', ':ownerId'),
                ),
            )
            ->setParameter('ownerType', $ownerType->value)
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
