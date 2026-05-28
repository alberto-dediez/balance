<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\List;

use App\Security\Application\SignatureRequest\List\ListSignatureRequestsRequest;
use App\Security\Application\SignatureRequest\List\ListSignatureRequestsService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListSignatureRequestsServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());
        $repository->add(SignatureRequestMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListSignatureRequestsRequest(), UserMother::adminUser());

        $this->assertCount(3, $response->signatureRequests);
        $this->assertEquals(3, $response->totalResults);
    }

    private function createSUT(SignatureRequestRepository $repository): ListSignatureRequestsService
    {
        return new ListSignatureRequestsService(
            repository: $repository,
            authorizer: new SignatureRequestAuthorizer(),
        );
    }
}
