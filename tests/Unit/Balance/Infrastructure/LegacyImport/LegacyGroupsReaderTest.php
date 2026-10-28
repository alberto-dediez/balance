<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDataset;
use App\Balance\Infrastructure\LegacyImport\LegacyGroups;
use App\Balance\Infrastructure\LegacyImport\LegacyGroupsReader;
use PHPUnit\Framework\TestCase;

class LegacyGroupsReaderTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->files = [];
    }

    public function testReadsTheAssignmentsByHeaderName(): void
    {
        $groups = $this->read(
            "slug;longitude;group;organization_id;latitude;bioscore_hotel_id;postal_code;address;users\n"
            . "hotel-a;-0.12;Grupo A ;org-a;38.53;44397;03501;Calle Mayor 1;a@example.test|b@example.test\n",
        );

        $assignment = $groups->assignment('org-a');

        $this->assertNotNull($assignment);
        $this->assertSame('org-a', $assignment->organizationId);
        $this->assertSame('Grupo A', $assignment->group);
        $this->assertSame('44397', $assignment->bioscoreHotelId);
        $this->assertSame('38.53', $assignment->latitude);
        $this->assertSame('-0.12', $assignment->longitude);
        $this->assertSame('Calle Mayor 1', $assignment->address);
        $this->assertSame('03501', $assignment->postalCode);
        $this->assertSame('Grupo A', $groups->groupOf('org-a'));
        $this->assertNull($groups->assignment('org-b'));
    }

    public function testTreatsEmptyValuesAsNull(): void
    {
        $groups = $this->read("organization_id;group;bioscore_hotel_id;latitude;longitude\norg-a; ;;;\n");

        $assignment = $groups->assignment('org-a');

        $this->assertNotNull($assignment);
        $this->assertNull($assignment->group);
        $this->assertNull($assignment->bioscoreHotelId);
        $this->assertNull($assignment->latitude);
        $this->assertNull($assignment->longitude);
        $this->assertNull($assignment->address);
        $this->assertNull($assignment->postalCode);
        $this->assertNull($groups->groupOf('org-a'));
    }

    public function testThrowsWhenTheFileDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no existe');

        (new LegacyGroupsReader())->read(sys_get_temp_dir() . '/unknown-groups-' . bin2hex(random_bytes(4)) . '.csv', $this->dataset());
    }

    public function testThrowsWithoutOrganizationIdColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('organization_id');

        $this->read("slug;group\nhotel-a;Grupo A\n");
    }

    public function testThrowsWhenTheOrganizationDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('org-unknown');

        $this->read("organization_id;group\norg-unknown;Grupo A\n");
    }

    public function testThrowsWhenTheOrganizationIsRepeated(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('repetida');

        $this->read("organization_id;group\norg-a;Grupo A\norg-a;Grupo B\n");
    }

    public function testThrowsWhenTheBioscoreHotelIsInTwoGroups(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"44397" está en dos grupos: "Grupo A" y "Grupo B"');

        $this->read("organization_id;group;bioscore_hotel_id\norg-a;Grupo A;44397\norg-b;Grupo B;44397\n");
    }

    public function testThrowsWhenTheBioscoreHotelIsInTwoUngroupedOrganizations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"44397" está en dos grupos: "sin grupo (org-a)" y "sin grupo (org-b)"');

        $this->read("organization_id;group;bioscore_hotel_id\norg-a;;44397\norg-b;;44397\n");
    }

    public function testAllowsTheSameBioscoreHotelTwiceInTheSameGroup(): void
    {
        $groups = $this->read("organization_id;group;bioscore_hotel_id\norg-a;Grupo A;44397\norg-b;Grupo A;44397\n");

        $this->assertCount(2, $groups->assignments);
    }

    public function testThrowsWithNonNumericCoordinates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no son numéricas');

        $this->read("organization_id;latitude;longitude\norg-a;norte;-0.12\n");
    }

    public function testThrowsWithOnlyOneCoordinate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('solo una coordenada');

        $this->read("organization_id;latitude;longitude\norg-a;38.53;\n");
    }

    private function read(string $content): LegacyGroups
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'groups');
        $this->files[] = $path;
        file_put_contents($path, $content);

        return (new LegacyGroupsReader())->read($path, $this->dataset());
    }

    private function dataset(): LegacyBalanceDataset
    {
        return new LegacyBalanceDataset(
            organizations: [
                LegacyOrganizationMother::from('hotel-a', id: 'org-a'),
                LegacyOrganizationMother::from('hotel-b', id: 'org-b'),
            ],
            users: [],
            memberships: [],
            calculations: [],
            donations: [],
            ecoProjects: [],
        );
    }
}
