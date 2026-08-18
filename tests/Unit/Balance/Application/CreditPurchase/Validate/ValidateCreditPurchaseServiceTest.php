<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Validate;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseNotPendingException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\CreditPurchase\DuplicatedPurchaseReferenceException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ValidateCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testValidatesFullAmount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $result = $sut(
            new ValidateCreditPurchaseRequest($creditPurchase->id(), 10.0, 'REF-1', CreditPurchasePaymentType::PaidByClient),
            $user,
        );

        $this->assertFalse($result->partiallyFulfilled);
        $this->assertSame(CreditPurchasePaymentType::PaidByClient, $creditPurchase->paymentType());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
    }

    public function testItStoresTheCostOfTheLot(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);
        $repository->add($creditPurchase);

        $this->createSUT($repository)(
            new ValidateCreditPurchaseRequest(
                $creditPurchase->id(),
                8.0,
                'REF-COST',
                CreditPurchasePaymentType::PaidByClient,
                600.0,
            ),
            UserMother::adminUser(),
        );

        $this->assertSame(600.0, $creditPurchase->amount());
        $this->assertSame(75.0, $creditPurchase->pricePerTonne());
    }

    public function testTheCostOfTheLotIsOptional(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);
        $repository->add($creditPurchase);

        $this->createSUT($repository)(
            new ValidateCreditPurchaseRequest($creditPurchase->id(), 10.0, 'REF-NO-COST', CreditPurchasePaymentType::PaidByClient),
            UserMother::adminUser(),
        );

        $this->assertNull($creditPurchase->amount());
        $this->assertNull($creditPurchase->pricePerTonne());
    }

    public function testThrowsWhenPaymentTypeIsMissing(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ValidateCreditPurchaseRequest($creditPurchase->id(), 10.0, 'REF-0'), $user);
    }

    public function testValidatesPartialAmount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $result = $sut(
            new ValidateCreditPurchaseRequest($creditPurchase->id(), 6.0, 'REF-2', CreditPurchasePaymentType::PaidByClient),
            $user,
        );

        $this->assertTrue($result->partiallyFulfilled);
    }

    public function testThrowsWhenReferenceIsDuplicated(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $alreadyValidated = CreditPurchaseMother::validated();
        $repository->add($alreadyValidated);

        $pending = CreditPurchaseMother::pending();
        $repository->add($pending);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $this->expectException(DuplicatedPurchaseReferenceException::class);

        $sut(
            new ValidateCreditPurchaseRequest($pending->id(), 5.0, (string) $alreadyValidated->reference(), CreditPurchasePaymentType::PaidByClient),
            $user,
        );
    }

    public function testThrowsWhenNotPending(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::validated();
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $sut(new ValidateCreditPurchaseRequest($creditPurchase->id(), 5.0, 'REF-3', CreditPurchasePaymentType::PaidByClient), $user);
    }

    public function testThrowsWhenUserIsNotAdmin(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending();
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ValidateCreditPurchaseRequest($creditPurchase->id(), 5.0, 'REF-4', CreditPurchasePaymentType::PaidByClient), $user);
    }

    private function createSUT(CreditPurchaseRepository $repository): ValidateCreditPurchaseService
    {
        return new ValidateCreditPurchaseService($repository, new CreditPurchaseAuthorizer());
    }
}
