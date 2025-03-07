<?php

namespace App\Tests\Unit\Security\Application\Account\List;

use PHPUnit\Framework\TestCase;
use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Application\Account\List\ListAccountsResponse;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class ListAccountsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());

        $request = new ListAccountsRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListAccountsResponse);
        $this->assertCount(4, $result->accounts);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListAccountsService($repository);
    }
}