<?php

namespace App\Security\Infrastructure\Repository\Account;

use Doctrine\ORM\EntityRepository;
use App\Security\Domain\Model\Account\Account;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.account', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAccountRepository implements AccountRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(Account::class);
    }

    public function add(Account $account): void
    {
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }

    public function remove(Account $account): void
    {
        $this->getEntityManager()->remove($account);
        $this->getEntityManager()->flush();
    }

    public function find(AccountId $accountId): ?Account
    {
        return $this->repository->findOneBy([
            'id.id' => (string) $accountId
        ]);
    }

    public function findByName(string $name): ?Account
    {
        return $this->repository->findOneByName($name);
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}