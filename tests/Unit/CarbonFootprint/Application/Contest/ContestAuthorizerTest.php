<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;

class ContestAuthorizerTest extends ContestApplicationTestCase
{
    private const array ACTIONS = [Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export];

    public function testOwnerOfTheCurrentAccountCanDoEverything(): void
    {
        $contest = ContestMother::draft(accountId: self::ACCOUNT);

        foreach (self::ACTIONS as $action) {
            $this->assertTrue($this->authorizer()->canDoThat($contest, $action, $this->owner()));
        }
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $contest = ContestMother::draft(accountId: self::OTHER_ACCOUNT);

        foreach (self::ACTIONS as $action) {
            $this->assertFalse($this->authorizer()->canDoThat($contest, $action, $this->owner()));
        }
    }

    public function testAdminWithoutImpersonatingIsDenied(): void
    {
        $contest = ContestMother::draft(accountId: self::ACCOUNT);

        foreach (self::ACTIONS as $action) {
            $this->assertFalse($this->authorizer()->canDoThat($contest, $action, $this->admin()));
        }
    }

    public function testAdminImpersonatingIsAllowed(): void
    {
        $contest = ContestMother::draft(accountId: self::ACCOUNT);
        $sut = $this->authorizer(roles: [Role::Owner, Role::User]);

        foreach (self::ACTIONS as $action) {
            $this->assertTrue($sut->canDoThat($contest, $action, $this->admin()));
        }
    }

    public function testUserIsDenied(): void
    {
        $contest = ContestMother::draft(accountId: self::ACCOUNT);

        foreach (self::ACTIONS as $action) {
            $this->assertFalse($this->authorizer()->canDoThat($contest, $action, $this->user()));
        }
    }

    public function testAccountWithoutTheFeatureIsDenied(): void
    {
        $contest = ContestMother::draft(accountId: self::ACCOUNT_WITHOUT_CONTESTS);
        $sut = $this->authorizer(self::ACCOUNT_WITHOUT_CONTESTS);

        foreach (self::ACTIONS as $action) {
            $this->assertFalse($sut->canDoThat($contest, $action, $this->owner(self::ACCOUNT_WITHOUT_CONTESTS)));
        }
    }

    public function testOnlySupportsContests(): void
    {
        $this->assertTrue($this->authorizer()->supports(ContestMother::draft()));
        $this->assertFalse($this->authorizer()->supports(PropertyMother::registeredProperty()));
    }
}
