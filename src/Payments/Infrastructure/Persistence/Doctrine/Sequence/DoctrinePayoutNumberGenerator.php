<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Sequence;

use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutNumberGenerator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePayoutNumberGenerator implements PayoutNumberGenerator
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function next(\DateTimeImmutable $on): string
    {
        $year = (int) $on->format('Y');

        $qb = $this->manager->createQueryBuilder();

        /** @var Payout|null $last */
        $last = $qb
            ->select('p')
            ->from(Payout::class, 'p')
            ->andWhere($qb->expr()->like('p.number', ':prefix'))
            ->setParameter('prefix', sprintf('%d/%%', $year))
            ->orderBy('p.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult()
        ;

        $sequence = null !== $last ? ((int) substr((string) $last->number(), -4)) + 1 : 1;

        return sprintf('%d/%04d', $year, $sequence);
    }
}
