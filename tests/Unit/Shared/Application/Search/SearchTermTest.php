<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Search;

use App\Shared\Application\Search\SearchTerm;
use PHPUnit\Framework\TestCase;

class SearchTermTest extends TestCase
{
    public function testTrimsTheValue(): void
    {
        $term = new SearchTerm('  acme  ');

        $this->assertSame('acme', $term->value);
        $this->assertSame('acme', (string) $term);
    }

    public function testASingleCharacterIsNotSearchable(): void
    {
        $this->assertFalse((new SearchTerm('a'))->isSearchable());
    }

    public function testTwoCharactersAreSearchable(): void
    {
        $this->assertTrue((new SearchTerm('ab'))->isSearchable());
    }

    public function testSurroundingSpacesDoNotCount(): void
    {
        $this->assertTrue((new SearchTerm('  ab  '))->isSearchable());
        $this->assertFalse((new SearchTerm('  a  '))->isSearchable());
    }
}
