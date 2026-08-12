<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.media', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMediaRepository implements MediaRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Media $media): void
    {
        $this->manager->persist($media);
        $this->manager->flush();
    }

    public function remove(Media $media): void
    {
        $this->manager->remove($media);
        $this->manager->flush();
    }

    public function find(MediaId $mediaId): ?Media
    {
        $qb = $this->createQueryBuilderFromFilter(new MediaFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', $mediaId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MediaFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(Media::class, 'm')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('m.removed'),
                    $qb->expr()->eq('m.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!in_array($filter->name, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('m.name', ':name'),
                        $qb->expr()->like('m.fileName', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->type) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.type', ':type'),
                )
                ->setParameter('type', $filter->type->value)
            ;
        }

        if ([] !== $filter->types) {
            $qb
                ->andWhere(
                    $qb->expr()->in('m.type', ':types'),
                )
                ->setParameter('types', array_map(static fn($type): string => $type->value, $filter->types))
            ;
        }

        if ($filter->visibility instanceof MediaVisibility) {
            $qb
                ->andWhere(
                    $qb->expr()->in('m.visibility', ':visibilities'),
                )
                ->setParameter(
                    'visibilities',
                    array_map(
                        static fn(MediaVisibility $visibility): string => $visibility->value,
                        MediaVisibility::casesSatisfying($filter->visibility),
                    ),
                )
            ;
        }

        $accountScope = $qb->expr()->andX();

        if ($filter->accountId instanceof AccountId) {
            $accountScope->add($qb->expr()->eq('m.accountId', ':accountId'));

            $qb->setParameter('accountId', $filter->accountId);
        }

        if (true === $filter->withoutAccount) {
            $accountScope->add($qb->expr()->isNull('m.accountId'));
        }

        if ($accountScope->count() > 0) {
            if (true === $filter->includeShared) {
                $qb
                    ->andWhere(
                        $qb->expr()->orX(
                            $accountScope,
                            $qb->expr()->in('m.visibility', ':sharedVisibilities'),
                        ),
                    )
                    ->setParameter(
                        'sharedVisibilities',
                        array_map(static fn(MediaVisibility $visibility): string => $visibility->value, MediaVisibility::sharedCases()),
                    )
                ;
            } else {
                $qb->andWhere($accountScope);
            }
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'name' => $qb->orderBy('m.name', $way),
            'size' => $qb->orderBy('m.size', $way),
            default => $qb->orderBy('m.registeredAt', $way),
        };

        return $qb;
    }

    /**
     * @return list<Media>
     */
    public function findByFilter(MediaFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(MediaFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $count = $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
