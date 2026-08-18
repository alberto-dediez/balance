<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payee;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Shared\Domain\Model\Address;

class PayeeMother
{
    public static function ready(?string $subjectId = null, PayeeType $type = PayeeType::Account): Payee
    {
        return self::from(
            type: $type,
            subjectId: $subjectId ?? (string) PayeeId::generate(),
            iban: 'ES9121000418450200051332',
            holderName: 'Hotel X SL',
        );
    }

    public static function incomplete(?string $subjectId = null, PayeeType $type = PayeeType::Account): Payee
    {
        return self::from(
            type: $type,
            subjectId: $subjectId ?? (string) PayeeId::generate(),
            iban: null,
            holderName: null,
        );
    }

    public static function blocked(?string $subjectId = null): Payee
    {
        $payee = self::ready($subjectId);
        $payee->block();

        return $payee;
    }

    public static function random(): Payee
    {
        return self::from(legalName: 'Payee ' . uniqid('', true));
    }

    public static function from(
        PayeeType $type = PayeeType::Account,
        ?string $subjectId = null,
        string $legalName = 'Hotel X SL',
        ?string $taxId = 'B12345678',
        ?string $iban = 'ES9121000418450200051332',
        ?string $bic = 'CAIXESBBXXX',
        ?string $holderName = 'Hotel X SL',
        ?string $notes = null,
    ): Payee {
        $payee = new Payee(id: PayeeId::generate());

        $payee->register(
            type: $type,
            subjectId: $subjectId ?? (string) PayeeId::generate(),
            legalName: $legalName,
            taxId: $taxId,
            address: new Address(street: 'Calle Mayor 1', postalCode: '28001', city: 'Madrid', country: 'ES'),
            iban: $iban,
            bic: $bic,
            holderName: $holderName,
            notes: $notes,
        );

        return $payee;
    }
}
