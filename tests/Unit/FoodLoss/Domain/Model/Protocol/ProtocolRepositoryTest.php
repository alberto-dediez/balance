<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\Model\Protocol\ProtocolFilter;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProtocolRepositoryTest extends KernelTestCase
{
    public function testAddProtocolToRepository(): void
    {
        $repository = $this->createSUT();

        $protocol = ProtocolMother::registeredProtocol();

        $this->assertNull($repository->find($protocol->id()));

        $repository->add($protocol);

        $recoveredProtocol = $repository->find($protocol->id());

        $this->assertNotNull($recoveredProtocol);
        $this->assertEquals($protocol->id(), $recoveredProtocol->id());
    }

    public function testRemoveProtocolFromRepository(): void
    {
        $repository = $this->createSUT();

        $protocol = ProtocolMother::registeredProtocol();

        $repository->add($protocol);

        $recoveredProtocol = $repository->find($protocol->id());

        $this->assertNotNull($recoveredProtocol);

        $repository->remove($protocol);

        $this->assertNull($repository->find($protocol->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProtocolMother::random());
        $repository->add(ProtocolMother::random());
        $repository->add(ProtocolMother::random());
        $repository->add(ProtocolMother::fromName('Protocolo específico'));

        $filter = new ProtocolFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);

        $filter = new ProtocolFilter(
            name: 'Protocolo específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = new AccountId('account-test');

        $repository->add(ProtocolMother::forAccount('account-test'));
        $repository->add(ProtocolMother::forAccount('account-test'));
        $repository->add(ProtocolMother::forAccount('other-account'));

        $filter = new ProtocolFilter(accountId: $accountId);

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProtocolMother::random());
        $repository->add(ProtocolMother::random());
        $repository->add(ProtocolMother::random());

        $filter = new ProtocolFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $protocol = ProtocolMother::registeredProtocol();

        $repository->add($protocol);

        $recoveredProtocol = $repository->find($protocol->id());

        $this->assertNotNull($recoveredProtocol);

        $recoveredProtocol->remove();

        $repository->add($protocol);

        $this->assertNull($repository->find($protocol->id()));
    }

    abstract protected function createSUT(): ProtocolRepository;
}
