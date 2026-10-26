<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDataset;
use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDatasetReader;
use App\Balance\Infrastructure\LegacyImport\LegacyMembership;
use PHPUnit\Framework\TestCase;

class LegacyBalanceDatasetReaderTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/fixtures';

    public function testReadsTheDatesWithMilliseconds(): void
    {
        $organization = $this->dataset()->organizationBySlug('viajes-test');

        $this->assertNotNull($organization);
        $this->assertSame('2024-08-29 14:10:45.872', $organization->createdAt->format('Y-m-d H:i:s.v'));
        $this->assertSame('UTC', $organization->createdAt->getTimezone()->getName());
        $this->assertTrue($organization->isOta());
        $this->assertSame('Viajes Test', $organization->displayName);
    }

    public function testNormalizesTheCorruptedRole(): void
    {
        $memberships = $this->dataset()->membershipsOfUser('user-owner');

        $this->assertCount(2, $memberships);
        $this->assertSame('org-ota', $memberships[0]->organizationId);
        $this->assertSame(LegacyMembership::ROLE_ADMIN, $memberships[0]->role);
        $this->assertSame(LegacyMembership::ROLE_EXTERNAL, $memberships[1]->role);
    }

    public function testReadsTheUsers(): void
    {
        $dataset = $this->dataset();

        $admin = $dataset->userById('user-admin');
        $owner = $dataset->userById('user-owner');

        $this->assertNotNull($admin);
        $this->assertNotNull($owner);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue($admin->globalAdmin);
        $this->assertFalse($owner->globalAdmin);
    }

    public function testReadsTheCalculations(): void
    {
        $calculations = $this->dataset()->calculations;

        $this->assertCount(2, $calculations);

        $this->assertSame('viajes-test', $calculations[0]->referralSlug);
        $this->assertSame('car', $calculations[0]->transport);
        $this->assertTrue($calculations[0]->roundTrip);
        $this->assertSame(403278.5, $calculations[0]->distanceMeters);
        $this->assertSame(166809.7, $calculations[0]->emissionsTotalGrams);
        $this->assertNotNull($calculations[0]->origin);
        $this->assertSame('place-madrid', $calculations[0]->origin->placeId);
        $this->assertSame('Madrid, Spain', $calculations[0]->origin->formattedAddress);
        $this->assertSame(40.4895147, $calculations[0]->origin->latitude);

        $this->assertNull($calculations[1]->referralSlug);
        $this->assertNull($calculations[1]->origin);
        $this->assertFalse($calculations[1]->roundTrip);
        $this->assertNotNull($calculations[1]->destination);
        $this->assertSame('Valencia, Spain', $calculations[1]->destination->formattedAddress);
    }

    public function testReadsTheDonationsAndEcoProjects(): void
    {
        $dataset = $this->dataset();

        $donations = $dataset->donationsOfOrganization('org-ota');
        $ecoProject = $dataset->ecoProjectById('eco-1');

        $this->assertCount(1, $donations);
        $this->assertSame('c0063a20-fe4e-48c6-b91a-fed92e6e4ebc', $donations[0]->transactionUuid);
        $this->assertSame(50.0, $donations[0]->amount);
        $this->assertNotNull($ecoProject);
        $this->assertSame('Pla d’Almisera', $ecoProject->displayName);
        $this->assertNull($dataset->ecoProjectById('eco-unknown'));
    }

    public function testThrowsWithTheNameOfTheMissingFile(): void
    {
        $directory = sys_get_temp_dir() . '/legacy-balance-' . bin2hex(random_bytes(4));
        mkdir($directory);

        foreach (['Organization.json', 'User.json', 'OrganizationMember.json', 'CalculatedEmissions.json', 'EcoProject.json'] as $file) {
            copy(self::FIXTURES . '/' . $file, $directory . '/' . $file);
        }

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Donation.json');

            (new LegacyBalanceDatasetReader())->read($directory);
        } finally {
            array_map(unlink(...), glob($directory . '/*.json') ?: []);
            rmdir($directory);
        }
    }

    private function dataset(): LegacyBalanceDataset
    {
        return (new LegacyBalanceDatasetReader())->read(self::FIXTURES);
    }
}
