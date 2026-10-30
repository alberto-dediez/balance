<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\InvalidOperatorEmailException;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Shared\Domain\Model\Locale\Locale;
use PHPUnit\Framework\TestCase;

class OperatorContactTest extends TestCase
{
    public function testFromNormalizesEmptyValuesToNull(): void
    {
        $contact = OperatorContact::from('  ', '', null);

        $this->assertNull($contact->email());
        $this->assertNull($contact->name());
        $this->assertNull($contact->locale());
        $this->assertTrue($contact->isEmpty());
    }

    public function testFromTrimsValues(): void
    {
        $contact = OperatorContact::from(' franquicia@hotel.es ', ' Ana ', Locale::English);

        $this->assertSame('franquicia@hotel.es', $contact->email());
        $this->assertSame('Ana', $contact->name());
        $this->assertSame(Locale::English, $contact->locale());
        $this->assertFalse($contact->isEmpty());
        $this->assertSame('franquicia@hotel.es', (string) $contact);
    }

    public function testFromRejectsAnInvalidEmail(): void
    {
        $this->expectException(InvalidOperatorEmailException::class);

        OperatorContact::from('not-an-email', null, null);
    }

    public function testFromRejectsATooLongName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContact::from(null, str_repeat('a', 256), null);
    }

    public function testIsNotEmptyWithOnlyALocale(): void
    {
        $this->assertFalse(OperatorContact::from(null, null, Locale::Spanish)->isEmpty());
    }

    public function testEquals(): void
    {
        $contact = OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::Spanish);

        $this->assertTrue($contact->equals(OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::Spanish)));
        $this->assertFalse($contact->equals(OperatorContact::from('otro@hotel.es', 'Ana', Locale::Spanish)));
        $this->assertFalse($contact->equals(OperatorContact::from('franquicia@hotel.es', null, Locale::Spanish)));
        $this->assertFalse($contact->equals(OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::English)));
        $this->assertTrue((new OperatorContact())->equals(OperatorContact::from('', '', null)));
    }
}
