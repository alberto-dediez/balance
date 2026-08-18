<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Lock;

use App\Payments\Application\Payout\Create\PayoutCreationLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePayoutCreationLock implements PayoutCreationLock
{
    public function __construct(private EntityManagerInterface $manager) {}

    /**
     * @template T
     *
     * @param callable(): T $creation
     *
     * @return T
     */
    public function withLock(callable $creation): mixed
    {
        return $this->manager->wrapInTransaction(static fn(): mixed => $creation());
    }
}
