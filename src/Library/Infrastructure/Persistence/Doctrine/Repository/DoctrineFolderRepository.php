<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.folder', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineFolderRepository implements FolderRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Folder $folder): void
    {
        $this->manager->persist($folder);
        $this->manager->flush();
    }

    public function remove(Folder $folder): void
    {
        $this->manager->remove($folder);
        $this->manager->flush();
    }

    public function find(FolderId $folderId): ?Folder
    {
        $qb = $this->createQueryBuilderFromFilter(new FolderFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('f.id', ':id'),
            )
            ->setParameter('id', $folderId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByAccountAndName(?AccountId $accountId, string $name): ?Folder
    {
        $qb = $this->createQueryBuilderFromFilter(new FolderFilter());

        $qb
            ->andWhere(
                $qb->expr()->eq('LOWER(f.name)', 'LOWER(:exactName)'),
            )
            ->setParameter('exactName', $name)
        ;

        if ($accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('f.accountId', ':accountId'),
                )
                ->setParameter('accountId', $accountId)
            ;
        } else {
            $qb->andWhere(
                $qb->expr()->isNull('f.accountId'),
            );
        }

        return $qb
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(FolderFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('f')
            ->from(Folder::class, 'f')
        ;

        if (!in_array($filter->name, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('f.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('f.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if (true === $filter->withoutAccount) {
            $qb->andWhere(
                $qb->expr()->isNull('f.accountId'),
            );
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('f.registeredAt', $way),
            default => $qb->orderBy('f.name', $way),
        };

        return $qb;
    }

    /**
     * @return list<Folder>
     */
    public function findByFilter(FolderFilter $filter): array
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

    public function totalResultsByFilter(FolderFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $count = $qb->select('COUNT(DISTINCT f)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
