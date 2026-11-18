<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\ToolResultContent;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolResult;
use PHPUnit\Framework\TestCase;

class ToolResultContentTest extends TestCase
{
    public function testWithoutLinkItIsTheJsonOfTheData(): void
    {
        $result = new ToolResult('1 cuenta', ['name' => 'Acmé', 'url' => 'a/b']);

        $this->assertSame('{"name":"Acmé","url":"a/b"}', ToolResultContent::of($result));
    }

    public function testWithLinkAndReferencesItAddsTheLink(): void
    {
        $result = new ToolResult(
            'Cuenta Acme',
            ['name' => 'Acme'],
            ['accountId' => '0190'],
            new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        );

        $this->assertSame(
            '{"name":"Acme","_link":{"label":"Ver cuenta","href":"route:admin_accounts_edit?accountId=0190"}}',
            ToolResultContent::of($result),
        );
    }

    public function testWithLinkAndAMissingReferenceItHasNoLink(): void
    {
        $result = new ToolResult(
            'Cuenta Acme',
            ['name' => 'Acme'],
            [],
            new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        );

        $this->assertSame('{"name":"Acme"}', ToolResultContent::of($result));
    }
}
