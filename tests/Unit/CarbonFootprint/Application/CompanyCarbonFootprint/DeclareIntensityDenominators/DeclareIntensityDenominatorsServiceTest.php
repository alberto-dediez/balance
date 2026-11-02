<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintIntensityDenominatorsDeclared;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

final class DeclareIntensityDenominatorsServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private SpyDomainEventSubscriber $spy;
    private DeclareIntensityDenominatorsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->sut = new DeclareIntensityDenominatorsService($this->repository, new CompanyCarbonFootprintAuthorizer());
    }

    public function testDeclaresAndPublishes(): void
    {
        $footprint = $this->footprint();

        $response = ($this->sut)($this->request($footprint, stays: 41000, builtAreaM2: '2000.00'), $this->owner());

        $this->assertSame(41000, $response->companyCarbonFootprint->intensityDenominators()->stays());
        $this->assertSame('2000.00', $footprint->intensityDenominators()->builtAreaM2());
        $this->assertCount(1, $this->declaredEvents());
    }

    public function testDeclaresOnACompletedFootprint(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('subject-1', 'footprint-2', 'account-1');
        $footprint->complete(new Emissions('1000', '1000'));
        $this->repository->add($footprint);

        ($this->sut)($this->request($footprint, stays: 100), $this->owner());

        $this->assertSame(100, $footprint->intensityDenominators()->stays());
        $this->assertTrue($footprint->isCompleted());
    }

    public function testSameValuesDoNotPublish(): void
    {
        $footprint = $this->footprint();
        ($this->sut)($this->request($footprint, stays: 100), $this->owner());

        ($this->sut)($this->request($footprint, stays: 100), $this->owner());

        $this->assertCount(1, $this->declaredEvents());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->footprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request($footprint, stays: 100), UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('account-2')));
    }

    public function testNegativeValueThrows(): void
    {
        $footprint = $this->footprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request($footprint, stays: -5), $this->owner());
    }

    private function footprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from('company-1', 'footprint-1', 'account-1', 2025);
        $this->repository->add($footprint);

        return $footprint;
    }

    private function request(CompanyCarbonFootprint $footprint, ?int $stays = null, ?string $builtAreaM2 = null): DeclareIntensityDenominatorsRequest
    {
        return new DeclareIntensityDenominatorsRequest(
            companyCarbonFootprintId: $footprint->id(),
            stays: $stays,
            builtAreaM2: $builtAreaM2,
        );
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));
    }

    /** @return list<object> */
    private function declaredEvents(): array
    {
        return array_values(array_filter($this->spy->events(), static fn(object $event): bool => $event instanceof CompanyCarbonFootprintIntensityDenominatorsDeclared));
    }
}
