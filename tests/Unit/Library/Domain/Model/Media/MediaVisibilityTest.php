<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Media\MediaVisibility;
use PHPUnit\Framework\TestCase;

class MediaVisibilityTest extends TestCase
{
    public function testTheSharedVisibilitiesEscapeTheAccount(): void
    {
        $this->assertFalse(MediaVisibility::Account->isShared());
        $this->assertTrue(MediaVisibility::System->isShared());
        $this->assertTrue(MediaVisibility::Public->isShared());
    }

    public function testAWiderVisibilitySatisfiesANarrowerRequirement(): void
    {
        $this->assertTrue(MediaVisibility::Public->satisfies(MediaVisibility::System));
        $this->assertTrue(MediaVisibility::System->satisfies(MediaVisibility::System));
        $this->assertTrue(MediaVisibility::Account->satisfies(MediaVisibility::Account));
    }

    public function testANarrowerVisibilityDoesNotSatisfyAWiderRequirement(): void
    {
        $this->assertFalse(MediaVisibility::Account->satisfies(MediaVisibility::System));
        $this->assertFalse(MediaVisibility::System->satisfies(MediaVisibility::Public));
    }
}
