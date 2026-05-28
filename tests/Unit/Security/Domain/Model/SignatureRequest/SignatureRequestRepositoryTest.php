<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestFilter;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class SignatureRequestRepositoryTest extends KernelTestCase
{
    public function testAddSignatureRequestToRepository(): void
    {
        $repository = $this->createSUT();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $this->assertNull($repository->find($signatureRequest->id()));

        $repository->add($signatureRequest);

        $recovered = $repository->find($signatureRequest->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($signatureRequest->id(), $recovered->id());
    }

    public function testRemoveSignatureRequestFromRepository(): void
    {
        $repository = $this->createSUT();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $repository->add($signatureRequest);

        $recovered = $repository->find($signatureRequest->id());

        $this->assertNotNull($recovered);

        $repository->remove($signatureRequest);

        $this->assertNull($repository->find($signatureRequest->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::fromAccountId('specific-account'));

        $filter = new SignatureRequestFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);

        $filter = new SignatureRequestFilter(
            accountId: new \App\Security\Domain\Model\Account\AccountId('specific-account'),
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());

        $filter = new SignatureRequestFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $repository->add($signatureRequest);

        $recovered = $repository->find($signatureRequest->id());

        $this->assertNotNull($recovered);

        $recovered->remove();

        $repository->add($signatureRequest);

        $this->assertNull($repository->find($signatureRequest->id()));
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $pending = SignatureRequestMother::registeredSignatureRequest();
        $repository->add($pending);

        $filter = new SignatureRequestFilter(status: SignatureRequestStatus::Pending);

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals(SignatureRequestStatus::Pending, $result[0]->status());
    }

    abstract protected function createSUT(): SignatureRequestRepository;
}
