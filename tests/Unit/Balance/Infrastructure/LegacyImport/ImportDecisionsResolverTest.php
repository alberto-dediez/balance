<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\ImportDecisionsResolver;
use App\Balance\Infrastructure\LegacyImport\ImportOptions;
use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDataset;
use App\Balance\Infrastructure\LegacyImport\LegacyCalculation;
use App\Balance\Infrastructure\LegacyImport\LegacyGroupAssignment;
use App\Balance\Infrastructure\LegacyImport\LegacyGroups;
use App\Balance\Infrastructure\LegacyImport\LegacyOrganization;
use App\Balance\Infrastructure\LegacyImport\OrganizationDecision;
use App\Balance\Infrastructure\LegacyImport\OrganizationTarget;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
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
            LegacyGroups::empty(),
        );

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-option'))->equals($decision->accountId));
    }

    public function testThrowsWhenTheAccountOptionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->organization(LegacyOrganizationMother::ota('sao'), new ImportOptions(accounts: ['sao' => 'unknown']), LegacyGroups::empty());
    }

    public function testPreselectsTheAccountWithTheSameName(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('SAÓ Viajes Naturales', 'account-by-name'));

        $decision = $this->createSUT()->organization(LegacyOrganizationMother::ota('sao', 'SAÓ Viajes Naturales'), new ImportOptions(), LegacyGroups::empty());

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-by-name'))->equals($decision->accountId));
    }

    public function testPreselectsCreateWithoutMatchingAccount(): void
    {
        $decision = $this->createSUT()->organization(LegacyOrganizationMother::hotel('coolrooms', 'CoolRooms'), new ImportOptions(), LegacyGroups::empty());

        $this->assertSame(OrganizationTarget::Create, $decision->target);
        $this->assertNull($decision->accountId);
        $this->assertFalse($decision->staff);
    }

    public function testSkipsAndMarksStaff(): void
    {
        $decision = $this->createSUT()->organization(
            LegacyOrganizationMother::hotel('bioscore'),
            new ImportOptions(skip: ['bioscore'], staff: ['bioscore']),
            LegacyGroups::empty(),
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
            LegacyGroups::empty(),
            'https://balance.example.test',
            interactive: false,
        );

        $this->assertSame('https://balance.example.test', $decisions->legacyUrl);
        $this->assertSame(OrganizationTarget::Reuse, $decisions->organization('org-viajes')?->target);
        $this->assertSame(OrganizationTarget::Create, $decisions->organization('org-hotel')?->target);
        $this->assertSame(OrganizationTarget::Skip, $decisions->organization('org-test')?->target);
        $this->assertSame(['plane'], array_keys($decisions->mobilities));
        $this->assertSame(['eco-1'], array_keys($decisions->projects));
        $this->assertNotNull($decisions->projects['eco-1']);
        $this->assertTrue($project->id()->equals($decisions->projects['eco-1']));
    }

    public function testUsesTheGroupNameToFindTheAccount(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('RH Hoteles', 'account-group'));

        $decision = $this->createSUT()->organization(
            LegacyOrganizationMother::from('hotel-rh-princesa', id: 'org-princesa'),
            new ImportOptions(),
            $this->groups(['org-princesa' => 'RH Hoteles']),
        );

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-group'))->equals($decision->accountId));
        $this->assertSame('RH Hoteles', $decision->group);
        $this->assertSame('RH Hoteles', $decision->accountKey);
    }

    public function testUsesTheAccountOptionKeyedByGroup(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Otra cuenta', 'account-option'));

        $decision = $this->createSUT()->organization(
            LegacyOrganizationMother::from('hotel-rh-princesa', id: 'org-princesa'),
            new ImportOptions(accounts: ['RH Hoteles' => 'account-option']),
            $this->groups(['org-princesa' => 'RH Hoteles']),
        );

        $this->assertSame(OrganizationTarget::Reuse, $decision->target);
        $this->assertNotNull($decision->accountId);
        $this->assertTrue((new AccountId('account-option'))->equals($decision->accountId));
    }

    public function testThrowsWhenTheAccountOptionUsesTheSlugOfAGroupedOrganization(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Otra cuenta', 'account-option'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pertenece al grupo "RH Hoteles"');

        $this->createSUT()->organization(
            LegacyOrganizationMother::from('hotel-rh-princesa', id: 'org-princesa'),
            new ImportOptions(accounts: ['hotel-rh-princesa' => 'account-option']),
            $this->groups(['org-princesa' => 'RH Hoteles']),
        );
    }

    public function testThrowsWhenTheStaffOptionUsesTheSlugOfAGroupedOrganization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--staff pertenece al grupo "RH Hoteles"');

        $this->createSUT()->organization(
            LegacyOrganizationMother::from('hotel-rh-princesa', id: 'org-princesa'),
            new ImportOptions(staff: ['hotel-rh-princesa']),
            $this->groups(['org-princesa' => 'RH Hoteles']),
        );
    }

    public function testMarksAsStaffEveryOrganizationOfTheStaffGroup(): void
    {
        $dataset = $this->dataset([
            LegacyOrganizationMother::from('hotel-a', id: 'org-a'),
            LegacyOrganizationMother::from('hotel-b', id: 'org-b'),
            LegacyOrganizationMother::from('hotel-c', id: 'org-c'),
        ]);

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(staff: ['Grupo A']),
            $this->groups(['org-a' => 'Grupo A', 'org-b' => 'Grupo A']),
            'https://balance.example.test',
            interactive: false,
        );

        $this->assertTrue($decisions->organization('org-a')?->staff);
        $this->assertTrue($decisions->organization('org-b')?->staff);
        $this->assertFalse($decisions->organization('org-c')?->staff);
    }

    public function testSharesTheAccountKeyInsideAGroup(): void
    {
        $dataset = $this->dataset([
            LegacyOrganizationMother::from('hotel-a', id: 'org-a'),
            LegacyOrganizationMother::from('hotel-b', id: 'org-b'),
            LegacyOrganizationMother::from('hotel-c', id: 'org-c'),
        ]);

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(),
            $this->groups(['org-a' => 'Grupo A', 'org-b' => 'Grupo A']),
            'https://balance.example.test',
            interactive: false,
        );

        $this->assertSame('Grupo A', $decisions->organization('org-a')?->accountKey);
        $this->assertSame('Grupo A', $decisions->organization('org-b')?->accountKey);
        $this->assertSame('org-c', $decisions->organization('org-c')?->accountKey);
        $this->assertCount(2, $decisions->decisionsOfAccountKey('Grupo A'));
    }

    public function testMarksTheConsultantOrganization(): void
    {
        $dataset = $this->dataset([
            LegacyOrganizationMother::from('hosbec', id: 'org-hosbec'),
            LegacyOrganizationMother::from('hotel', id: 'org-hotel'),
        ]);

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(consultant: 'hosbec', createProperties: true),
            LegacyGroups::empty(),
            'https://balance.example.test',
            interactive: false,
        );

        $consultant = $decisions->consultantDecision();

        $this->assertSame('org-hosbec', $decisions->consultantOrganizationId);
        $this->assertNotNull($consultant);
        $this->assertTrue($consultant->consultant);
        $this->assertFalse($consultant->createProperty);
        $this->assertFalse($decisions->organization('org-hotel')?->consultant);
    }

    public function testThrowsWhenTheConsultantDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--consultant');

        $this->createSUT()->resolve(
            $this->dataset([LegacyOrganizationMother::hotel('hotel')]),
            new ImportOptions(consultant: 'hosbec'),
            LegacyGroups::empty(),
            'https://balance.example.test',
            interactive: false,
        );
    }

    public function testThrowsWhenTheConsultantIsSkipped(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede omitirse');

        $this->createSUT()->resolve(
            $this->dataset([LegacyOrganizationMother::hotel('hosbec')]),
            new ImportOptions(skip: ['hosbec'], consultant: 'hosbec'),
            LegacyGroups::empty(),
            'https://balance.example.test',
            interactive: false,
        );
    }

    public function testPreselectsThePropertyWithTheSameBioscoreId(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel A', 'account-id', 'property-a'));
        $this->propertyRepository->add($this->bioscoreProperty('Otro nombre', 'account-id', 'property-b', '44397'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel-rh-princesa', 'Hotel RH Princesa'),
            $this->reuse('hotel-rh-princesa', 'account-id', 'RH Hoteles'),
            new ImportOptions(),
            $this->assignment('org-hotel-rh-princesa', 'RH Hoteles', '44397'),
        );

        $this->assertNotNull($propertyId);
        $this->assertSame('property-b', (string) $propertyId);
    }

    public function testIgnoresTheBioscorePropertyOfAnotherAccount(): void
    {
        $this->propertyRepository->add($this->bioscoreProperty('Hotel RH Princesa', 'another-account-id', 'property-b', '44397'));

        $decision = $this->reuse('hotel-rh-princesa', 'account-id', 'RH Hoteles');

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel-rh-princesa', 'Hotel RH Princesa'),
            $decision,
            new ImportOptions(createProperties: true),
            $this->assignment('org-hotel-rh-princesa', 'RH Hoteles', '44397'),
        );

        $this->assertNull($propertyId);
        $this->assertTrue($decision->createProperty);
    }

    public function testPreselectsTheBioscorePropertyOfTheAccountWhenAnotherHasTheSameBioscoreId(): void
    {
        $this->propertyRepository->add(PropertyMother::from(
            new PropertyId('property-catalog'),
            'Catálogo',
            externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '44397'),
        ));
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel A', 'account-id', 'property-a'));
        $this->propertyRepository->add($this->bioscoreProperty('Otro nombre', 'account-id', 'property-b', '44397'));

        $decision = $this->reuse('hotel-rh-princesa', 'account-id', 'RH Hoteles');

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel-rh-princesa', 'Hotel RH Princesa'),
            $decision,
            new ImportOptions(createProperties: true),
            $this->assignment('org-hotel-rh-princesa', 'RH Hoteles', '44397'),
        );

        $this->assertNotNull($propertyId);
        $this->assertSame('property-b', (string) $propertyId);
        $this->assertFalse($decision->createProperty);
    }

    public function testPreselectsThePropertyWithTheSameNormalizedName(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel A', 'account-id', 'property-a'));
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('HOTEL RH  PRINCESA', 'account-id', 'property-b'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel-rh-princesa', ' Hotel RH Princésa'),
            $this->reuse('hotel-rh-princesa', 'account-id', 'RH Hoteles'),
            new ImportOptions(),
        );

        $this->assertNotNull($propertyId);
        $this->assertSame('property-b', (string) $propertyId);
    }

    public function testDoesNotPickTheOnlyPropertyForAGroupedOrganization(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel RH Victoria', 'account-id', 'property-a'));

        $propertyId = $this->createSUT()->property(
            LegacyOrganizationMother::hotel('hotel-rh-princesa', 'Hotel RH Princesa'),
            $this->reuse('hotel-rh-princesa', 'account-id', 'RH Hoteles'),
            new ImportOptions(),
        );

        $this->assertNull($propertyId);
    }

    public function testMarksCreatePropertyWithTheOption(): void
    {
        $decision = new OrganizationDecision('hotel', OrganizationTarget::Create, accountKey: 'org-hotel');

        $propertyId = $this->createSUT()->property(LegacyOrganizationMother::hotel('hotel'), $decision, new ImportOptions(createProperties: true));

        $this->assertNull($propertyId);
        $this->assertTrue($decision->createProperty);
        $this->assertTrue($decision->hasProperty());
    }

    public function testLeavesWithoutPropertyWithoutTheOption(): void
    {
        $decision = $this->reuse('hotel', 'account-id', 'Grupo');

        $propertyId = $this->createSUT()->property(LegacyOrganizationMother::hotel('hotel'), $decision, new ImportOptions());

        $this->assertNull($propertyId);
        $this->assertFalse($decision->createProperty);
        $this->assertFalse($decision->hasProperty());
    }

    public function testConsultantCalculationsAreImportableWithoutProperty(): void
    {
        $dataset = $this->dataset(
            [LegacyOrganizationMother::from('hosbec', id: 'org-hosbec')],
            [LegacyCalculationMother::from('hosbec', 'car')],
        );

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(consultant: 'hosbec'),
            LegacyGroups::empty(),
            'https://balance.example.test',
            interactive: true,
        );

        $this->assertCount(1, $this->createSUT()->importableCalculations($dataset, $decisions));
    }

    public function testKeysTheDecisionsByOrganizationId(): void
    {
        $dataset = $this->dataset([
            LegacyOrganizationMother::from('duplicado', id: 'org-1'),
            LegacyOrganizationMother::from('duplicado', id: 'org-2'),
        ]);

        $decisions = $this->createSUT()->resolve(
            $dataset,
            new ImportOptions(),
            $this->groups(['org-1' => 'Duplicado', 'org-2' => 'Duplicado']),
            'https://balance.example.test',
            interactive: false,
        );

        $this->assertCount(2, $decisions->organizations);
        $this->assertNotSame($decisions->organization('org-1'), $decisions->organization('org-2'));
        $this->assertNull($decisions->organization('duplicado'));
    }

    public function testFindsSimilarAccountsIgnoringCaseAccentsAndLegalForm(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('ZT Hotels & Resorts', 'account-zt'));

        $accounts = $this->createSUT()->similarAccounts('ZT HOTELS & RESORTS S.L.');

        $this->assertCount(1, $accounts);
        $this->assertSame('account-zt', (string) $accounts[0]->id());
        $this->assertSame(ImportDecisionsResolver::nameKey('ZT Hotels & Resorts'), ImportDecisionsResolver::nameKey('ZT HOTELS & RESORTS S.L.'));
        $this->assertSame('hoteles poseidon', ImportDecisionsResolver::nameKey('Hoteles Poseidón, S.A.U.'));
    }

    public function testFindsSimilarAccountsByWholeWordInclusion(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Grupo RH Hoteles', 'account-rh'));
        $this->accountRepository->add(AccountMother::fromNameAndId('RHO Hotelesia', 'account-rho'));

        $accounts = $this->createSUT()->similarAccounts('RH Hoteles');

        $this->assertSame(['account-rh'], array_map(static fn(Account $account): string => (string) $account->id(), $accounts));
    }

    public function testDoesNotReturnTheAccountWithTheExactName(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('RH Hoteles', 'account-rh'));

        $this->assertSame([], $this->createSUT()->similarAccounts('RH Hoteles'));
    }

    public function testReturnsAtMostThreeSimilarAccountsSortedByName(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('RH Hoteles Valencia', 'account-d'));
        $this->accountRepository->add(AccountMother::fromNameAndId('Grupo RH Hoteles', 'account-b'));
        $this->accountRepository->add(AccountMother::fromNameAndId('RH Hoteles Benidorm', 'account-c'));
        $this->accountRepository->add(AccountMother::fromNameAndId('Cadena RH Hoteles', 'account-a'));

        $accounts = $this->createSUT()->similarAccounts('RH Hoteles');

        $this->assertSame(
            ['Cadena RH Hoteles', 'Grupo RH Hoteles', 'RH Hoteles Benidorm'],
            array_map(static fn(Account $account): string => $account->name(), $accounts),
        );
    }

    public function testReturnsNoSimilarAccountsForUnrelatedNames(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Port Hotels', 'account-port'));
        $this->accountRepository->add(AccountMother::fromNameAndId('Magic Costa Blanca', 'account-magic'));

        $this->assertSame([], $this->createSUT()->similarAccounts('RH Hoteles'));
    }

    private function reuse(string $slug, string $accountId, ?string $group = null): OrganizationDecision
    {
        return new OrganizationDecision($slug, OrganizationTarget::Reuse, new AccountId($accountId), accountKey: $group ?? 'org-' . $slug, group: $group);
    }

    /** @param array<string, string> $groups */
    private function groups(array $groups): LegacyGroups
    {
        $assignments = [];

        foreach ($groups as $organizationId => $group) {
            $assignments[$organizationId] = $this->assignment($organizationId, $group);
        }

        return new LegacyGroups($assignments);
    }

    private function assignment(string $organizationId, ?string $group, ?string $bioscoreHotelId = null): LegacyGroupAssignment
    {
        return new LegacyGroupAssignment($organizationId, $group, $bioscoreHotelId, null, null, null, null);
    }

    private function bioscoreProperty(string $name, string $accountId, string $id, string $bioscoreHotelId): Property
    {
        return PropertyMother::from(
            new PropertyId($id),
            $name,
            new AccountId($accountId),
            externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, $bioscoreHotelId),
        );
    }

    /**
     * @param list<LegacyOrganization> $organizations
     * @param list<LegacyCalculation>  $calculations
     */
    private function dataset(array $organizations, array $calculations = []): LegacyBalanceDataset
    {
        return new LegacyBalanceDataset($organizations, [], [], $calculations, [], []);
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
