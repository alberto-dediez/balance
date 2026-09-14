<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use PHPUnit\Framework\TestCase;

class ChangelogEntrySlugTest extends TestCase
{
    public function testItAcceptsAValidSlug(): void
    {
        $slug = new ChangelogEntrySlug('firma-electronica');

        $this->assertSame('firma-electronica', (string) $slug);
    }

    /**
     * @dataProvider invalidSlugProvider
     */
    public function testItRejectsAnInvalidSlug(string $value): void
    {
        $this->expectException(InvalidChangelogEntryException::class);

        new ChangelogEntrySlug($value);
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function invalidSlugProvider(): iterable
    {
        yield 'uppercase' => ['Firma'];
        yield 'underscore' => ['firma_electronica'];
        yield 'path traversal' => ['../secreto'];
        yield 'empty' => [''];
        yield 'trailing newline' => ["firma-electronica\n"];
    }

    public function testEquals(): void
    {
        $a = new ChangelogEntrySlug('firma-electronica');
        $b = new ChangelogEntrySlug('firma-electronica');
        $c = new ChangelogEntrySlug('otra-entrada');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
