<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\List\ListContestsRequest;
use App\CarbonFootprint\Application\Contest\List\ListContestsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;

class ListContestsServiceTest extends ContestApplicationTestCase
{
    public function testListsOnlyTheCurrentAccount(): void
    {
        $this->store(ContestMother::draft(accountId: self::ACCOUNT, name: 'Mío'));
        $this->store(ContestMother::draft(accountId: self::OTHER_ACCOUNT, name: 'Ajeno'));

        $request = new ListContestsRequest(accountId: new AccountId(self::OTHER_ACCOUNT));
        $response = $this->createSUT()($request, $this->owner());

        $this->assertSame(1, $response->totalResults);
        $this->assertSame('Mío', $response->contests[0]->name());
    }

    public function testAccountWithoutTheFeatureIsDenied(): void
    {
        $sut = new ListContestsService(
            $this->contestRepository,
            $this->authorizer(self::ACCOUNT_WITHOUT_CONTESTS),
            new StubCurrentAccountIdProvider(new AccountId(self::ACCOUNT_WITHOUT_CONTESTS)),
        );

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListContestsRequest(), $this->owner(self::ACCOUNT_WITHOUT_CONTESTS));
    }

    private function createSUT(): ListContestsService
    {
        return new ListContestsService(
            $this->contestRepository,
            $this->authorizer(),
            new StubCurrentAccountIdProvider(new AccountId(self::ACCOUNT)),
        );
    }
}
