<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Assistant;

use App\Shared\Application\Assistant\ToolLink;
use PHPUnit\Framework\TestCase;

class ToolLinkTest extends TestCase
{
    public function testHrefWithoutParameters(): void
    {
        $this->assertSame('route:admin_users', ToolLink::href('admin_users'));
    }

    public function testHrefEncodesParameters(): void
    {
        $this->assertSame(
            'route:admin_changelog_entry?slug=a%20b%26c&page=2',
            ToolLink::href('admin_changelog_entry', ['slug' => 'a b&c', 'page' => '2']),
        );
    }

    public function testHrefFromCollectsTheReferences(): void
    {
        $link = new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']);

        $this->assertSame(
            'route:admin_accounts_edit?accountId=0190',
            $link->hrefFrom(['accountId' => '0190', 'other' => 'x']),
        );
    }

    public function testHrefFromWithAMissingReferenceIsNull(): void
    {
        $link = new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']);

        $this->assertNull($link->hrefFrom(['userId' => '0190']));
    }
}
