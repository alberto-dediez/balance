<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\ImportDecisionsResolver;
use App\Balance\Infrastructure\LegacyImport\ImportOptions;
use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDataset;
use App\Balance\Infrastructure\LegacyImport\OrganizationDecision;
use App\Balance\Infrastructure\LegacyImport\OrganizationTarget;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class ImportDecisionsResolverTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryProjectRepository $projectRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountRepository = new InMemoryAccountRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->projectRepository = new InMemoryProjectRepository();
    }

    public function testPreselectsTheAccountOfTheAccountOption(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Otra cuenta', 'account-option'));
        $this->accountRepository->add(AccountMother::fromNameAndId('SAÓ Viajes Naturales', 'account-by-name'));

        $decision = $this->createSUT()->organization(
            LegacyOrganizationMother::ota('sao', 'SAÓ Viajes Naturales'),
            new ImportOptions(accounts: ['sao' => 'account-option']),
        );

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-option'))->equals($decision->accountId));
    }

    public function testThrowsWhenTheAccountOptionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->organization(LegacyOrganizationMother::ota('sao'), new ImportOptions(accounts: ['sao' => 'unknown']));
    }

    public function testPreselectsTheAccountWithTheSameName(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('SAÓ Viajes Naturales', 'account-by-name'));

        $decision = $this->createSUT()->organization(LegacyOrganizationMother::ota('sao', 'SAÓ Viajes Naturales'), new ImportOptions());

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-by-name'))->equals($decision->accountId));
    }

    public function testPreselectsCreateWithoutMatchingAccount(): void
    {
        $decision = $this->createSUT()->organization(LegacyOrganizationMother::hotel('coolrooms', 'CoolRooms'), new ImportOptions());

        $this->assertSame(OrganizationTarget::Create, $decision->target);
        $this->assertNull($decision->accountId);
        $this->assertFalse($decision->staff);
    }

    public function testSkipsAndMarksStaff(): void
    {
        $decision = $this->createSUT()->organization(
            LegacyOrganizationMother::hotel('bioscore'),
            new ImportOptions(skip: ['bioscore'], staff: ['bioscore']),
        );

        $this->assertSame(OrganizationTarget::Skip, $decision->target);
        $this->assertTrue($decision->staff);
    }

    public function testPreselectsTheOnlyPropertyOfTheAccount(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', 'property-id'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel'),
            $this->reuse('hotel', 'account-id'),
            new ImportOptions(),
        );

        $this->assertNotNull($propertyId);
        $this->assertSame('property-id', (string) $propertyId);
    }

    public function testLeavesWithoutPropertyWhenTheAccountHasSeveral(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel A', 'account-id', 'property-a'));
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel B', 'account-id', 'property-b'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel'),
            $this->reuse('hotel', 'account-id'),
            new ImportOptions(),
        );

        $this->assertNull($propertyId);
    }

    public function testPreselectsThePropertyOption(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel A', 'account-id', 'property-a'));
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel B', 'account-id', 'property-b'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel'),
            $this->reuse('hotel', 'account-id'),
            new ImportOptions(properties: ['hotel' => 'property-b']),
        );

        $this->assertNotNull($propertyId);
        $this->assertSame('property-b', (string) $propertyId);
    }

    public function testThrowsWhenThePropertyOptionBelongsToAnotherAccount(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel', 'another-account-id', 'property-id'));

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel'),
            $this->reuse('hotel', 'account-id'),
            new ImportOptions(properties: ['hotel' => 'property-id']),
        );
    }

    public function testPreselectsTheProjectOption(): void
    {
        $project = ProjectMother::fromNameAndId('Programa CAMINA', 'project-id');
        $this->projectRepository->add($project);

        $projectId = $this->createSUT()->project(
            LegacyEcoProjectMother::from('eco-1', 'Otro nombre'),
            new ImportOptions(projects: ['eco-1' => 'project-id']),
        );

        $this->assertNotNull($projectId);
        $this->assertTrue($project->id()->equals($projectId));
    }

    public function testPreselectsTheProjectWithTheSameNormalizedName(): void
    {
        $project = ProjectMother::fromNameAndId('BOSQUE VILLARGORDO DEL CABRIEL', 'project-id');
        $this->projectRepository->add($project);

        $projectId = $this->createSUT()->project(
            LegacyEcoProjectMother::from('eco-1', '  Bosque  Villargordo del Cabriél '),
            new ImportOptions(),
        );

        $this->assertNotNull($projectId);
        $this->assertTrue($project->id()->equals($projectId));
    }

    public function testComparesTheTypographicApostropheAsIs(): void
    {
        $this->projectRepository->add(ProjectMother::fromNameAndId("pla d'almisera", 'project-id'));

        $projectId = $this->createSUT()->project(LegacyEcoProjectMother::from('eco-1', 'Pla d’Almisera'), new ImportOptions());

        $this->assertNull($projectId);
    }

    public function testLeavesWithoutProjectWithoutMatch(): void
    {
        $this->projectRepository->add(ProjectMother::fromNameAndId('Solana De La Canal', 'project-id'));

        $projectId = $this->createSUT()->project(LegacyEcoProjectMother::from('eco-1', 'Programa CAMINA'), new ImportOptions());

        $this->assertNull($projectId);
    }

    public function testThrowsWithoutMobilityOptionWithoutInteraction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--mobility-plane');

        $this->createSUT()->mobility('plane', new ImportOptions(), interactive: false);
    }

    public function testLeavesWithoutMobilityOptionInteractively(): void
    {
        $this->assertNull($this->createSUT()->mobility('plane', new ImportOptions(), interactive: true));
    }

    public function testResolvesTheMobilityOption(): void
    {
        $this->mobilityRepository->add(MobilityMother::fromNameAndId('Avión', 'mobility-id'));

        $mobilityId = $this->createSUT()->mobility('plane', new ImportOptions(mobilities: ['plane' => 'mobility-id']), interactive: false);

        $this->assertNotNull($mobilityId);
        $this->assertSame('mobility-id', (string) $mobilityId);
    }

    public function testResolvesTheDecisionsWithoutInteraction(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Viajes', 'account-ota'));
        $this->mobilityRepository->add(MobilityMother::fromNameAndId('Avión', 'mobility-plane'));
        $project = ProjectMother::fromNameAndId('Programa CAMINA', 'project-id');
        $this->projectRepository->add($project);

        $dataset = new LegacyBalanceDataset(
            organizations: [
                LegacyOrganizationMother::ota('viajes', 'Viajes'),
                LegacyOrganizationMother::hotel('hotel', 'Hotel'),
                LegacyOrganizationMother::hotel('test', 'Test'),
            ],
            users: [],
            memberships: [],
            calculations: [
                LegacyCalculationMother::from('viajes', 'plane'),
                LegacyCalculationMother::from('hotel', 'car'),
                LegacyCalculationMother::from('test', 'bus'),
                LegacyCalculationMother::from(null, 'train'),
            ],
            donations: [
                LegacyDonationMother::from('org-viajes', 'eco-1'),
                LegacyDonationMother::from('org-test', 'eco-2'),
            ],
            ecoProjects: [LegacyEcoProjectMother::from('eco-1', 'Programa CAMINA')],
        );

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(skip: ['test'], mobilities: ['plane' => 'mobility-plane']),
            'https://balance.example.test',
            interactive: false,
        );

        $this->assertSame('https://balance.example.test', $decisions->legacyUrl);
        $this->assertSame(OrganizationTarget::Reuse, $decisions->organization('viajes')?->target);
        $this->assertSame(OrganizationTarget::Create, $decisions->organization('hotel')?->target);
        $this->assertSame(OrganizationTarget::Skip, $decisions->organization('test')?->target);
        $this->assertSame(['plane'], array_keys($decisions->mobilities));
        $this->assertSame(['eco-1'], array_keys($decisions->projects));
        $this->assertNotNull($decisions->projects['eco-1']);
        $this->assertTrue($project->id()->equals($decisions->projects['eco-1']));
    }

    private function reuse(string $slug, string $accountId): OrganizationDecision
    {
        return new OrganizationDecision($slug, OrganizationTarget::Reuse, new AccountId($accountId));
    }

    private function createSUT(): ImportDecisionsResolver
    {
        return new ImportDecisionsResolver(
            $this->accountRepository,
            $this->propertyRepository,
            $this->mobilityRepository,
            $this->projectRepository,
        );
    }
}
