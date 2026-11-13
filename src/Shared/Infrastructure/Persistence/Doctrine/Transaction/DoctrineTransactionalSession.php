<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Transaction;

use App\Shared\Application\Transaction\TransactionalSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTransactionalSession implements TransactionalSession
{
    public function __construct(private EntityManagerInterface $manager) {}

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $connection = $this->manager->getConnection();
        $connection->beginTransaction();

        try {
            $result = $operation();
            $connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            $connection->rollBack();
            $this->manager->clear();

            throw $exception;
        }
    }
}
