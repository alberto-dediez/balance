<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\RecordView;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Security\Domain\Model\SignatureRequest\Token;

class RecordSignatureViewService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(RecordSignatureViewRequest $request): RecordSignatureViewResponse
    {
        $tokenHash = Token::hashFor($request->tokenPlainValue, $this->appSecret);
        $signatureRequest = $this->repository->findByToken($tokenHash);

        if (null === $signatureRequest || $signatureRequest->isRemoved()) {
            throw new SignatureTokenInvalidException();
        }

        if ($signatureRequest->expiresAt() < new \DateTimeImmutable('NOW')) {
            throw new SignatureTokenInvalidException();
        }

        if (SignatureRequestStatus::Signed === $signatureRequest->status()) {
            return new RecordSignatureViewResponse(signatureRequest: $signatureRequest, alreadySigned: true);
        }

        $signatureRequest->recordView(
            signerIp: $request->signerIp,
            signerUserAgent: $request->signerUserAgent,
        );

        $this->repository->add($signatureRequest);

        return new RecordSignatureViewResponse(signatureRequest: $signatureRequest);
    }
}
