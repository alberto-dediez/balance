<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CompanyCarbonFootprintChoiceType;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyCarbonFootprintChoiceTypeTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprintRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->companyCarbonFootprintRepository = new InMemoryCompanyCarbonFootprintRepository();
    }

    public function testListsOnlyTheCompletedFootprintsOfTheCurrentAccount(): void
    {
        $own = $this->completedFootprint('account-id');
        $this->completedFootprint('other-account');
        $this->companyCarbonFootprintRepository->add(CompanyCarbonFootprintMother::withEmployeesAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id'));

        $choices = $this->choices(AccountMother::fromNameAndId('Acme', 'account-id'));

        self::assertSame([(string) $own->id()], array_values($choices));
    }

    public function testListsNoFootprintsWithoutCurrentAccount(): void
    {
        $this->completedFootprint('account-id');
        $this->completedFootprint('other-account');

        self::assertSame([], $this->choices(null));
    }

    /** @return array<string, string> */
    private function choices(?Account $account): array
    {
        $loggedAccount = $this->createStub(LoggedAccount::class);
        $loggedAccount->method('__invoke')->willReturn($account);

        $sut = new CompanyCarbonFootprintChoiceType($loggedAccount, $this->companyCarbonFootprintRepository, CompanyRepositoryMother::inMemory());

        $resolver = new OptionsResolver();
        $sut->configureOptions($resolver);

        /** @var array<string, string> $choices */
        $choices = $resolver->resolve()['choices'];

        return $choices;
    }

    private function completedFootprint(string $accountId): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), $accountId);
        $footprint->complete(new Emissions());
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }
}
