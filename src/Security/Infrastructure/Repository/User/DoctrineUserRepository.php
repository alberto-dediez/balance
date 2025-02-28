<?php

namespace App\Security\Infrastructure\Repository\User;

use Doctrine\ORM\EntityRepository;
use App\Security\Domain\Model\User\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.user', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineUserRepository implements UserRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(User::class);
    }

    public function add(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function find(UserId $userId): ?User
    {
        return $this->repository->findOneBy([
            'id.id' => (string) $userId
        ]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->repository->findOneByUsername($username);
    }

    private function createQueryBuilderFromFilter(UserFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u, a')
            ->from(User::class, 'u')
            ->leftJoin('u.account', 'a')
        ;

        if (!empty($filter->username)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.username', ':username')
                )
                ->setParameter('username', "%{$filter->username}%")
            ;
        }

        if (null !== $filter->role) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.roles', ':role')
                )
                ->setParameter('role', "%" . $filter->role->value . "%")
            ;
        }

        if (null !== $filter->account) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('u.accountId', ':accountId')
                )
                ->setParameter('accountId', (string) $filter->account->id())
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('u.registeredAt', $way);
                break;
            case 'username':
            default:
                $qb->orderBy('u.username', $way);        
                break;
        }

        return $qb;
    }

    public function findByFilter(UserFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(UserFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT u.username)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}