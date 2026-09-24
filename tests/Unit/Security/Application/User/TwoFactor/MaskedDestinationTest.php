<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\TwoFactor;

use App\Security\Application\User\TwoFactor\MaskedDestination;
use PHPUnit\Framework\TestCase;

class MaskedDestinationTest extends TestCase
{
    public function testMasksTheLocalPartOfTheEmail(): void
    {
        $this->assertEquals('u***@dediez.software', MaskedDestination::email('user@dediez.software'));
    }

    public function testKeepsASingleCharacterLocalPart(): void
    {
        $this->assertEquals('a@dediez.software', MaskedDestination::email('a@dediez.software'));
    }

    public function testValueWithoutDomain(): void
    {
        $this->assertEquals('u***@', MaskedDestination::email('user'));
    }
}
