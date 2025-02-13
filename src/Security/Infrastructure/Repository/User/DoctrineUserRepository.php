<?php

namespace App\Security\Infrastructure\Repository\User;

use Doctrine\ORM\EntityRepository;
use App\Security\Domain\Model\User\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
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

    private function getEntityManager()
    {
        return $this->manager;
    }
}