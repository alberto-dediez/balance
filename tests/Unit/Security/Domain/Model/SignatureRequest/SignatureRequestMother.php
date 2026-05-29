<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\Signer;
use App\Security\Domain\Model\SignatureRequest\Token;
use App\Shared\Domain\Model\Locale\Locale;

class SignatureRequestMother
{
    public static function registeredSignatureRequest(): SignatureRequest
    {
        return self::from(
            id: (string) SignatureRequestId::generate(),
            accountId: 'account-id-test',
        );
    }

    public static function fromAccountId(string $accountId): SignatureRequest
    {
        return self::from(
            id: (string) SignatureRequestId::generate(),
            accountId: $accountId,
        );
    }

    public static function fromIdAndAccountId(string $id, string $accountId): SignatureRequest
    {
        return self::from(id: $id, accountId: $accountId);
    }

    public static function from(
        string $id,
        string $accountId = 'account-id-test',
        string $signerEmail = 'signer@example.com',
        ?string $documentPath = null,
    ): SignatureRequest {
        $signatureRequest = new SignatureRequest(
            id: new SignatureRequestId($id),
        );

        $token = Token::generate('test-app-secret');
        $documentToSign = new DocumentReference(
            sha256: hash('sha256', 'test-document-content'),
            size: 1024,
            filename: 'test.pdf',
            path: $documentPath ?? '/tmp/test.pdf',
        );
        $signer = new Signer(
            name: 'Test Signer',
            locale: Locale::Spanish,
            email: $signerEmail,
            phone: null,
        );

        $signatureRequest->register(
            token: $token,
            documentToSign: $documentToSign,
            signer: $signer,
            accountId: new AccountId($accountId),
        );

        return $signatureRequest;
    }

    public static function random(): SignatureRequest
    {
        return self::from(
            id: (string) SignatureRequestId::generate(),
            accountId: 'account-' . random_int(100, 999),
            signerEmail: 'signer' . random_int(100, 999) . '@example.com',
        );
    }
}
