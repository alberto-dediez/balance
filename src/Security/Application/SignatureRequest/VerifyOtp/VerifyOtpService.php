<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\VerifyOtp;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Security\Domain\Model\SignatureRequest\Token;

class VerifyOtpService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(VerifyOtpRequest $request): VerifyOtpResponse
    {
        $tokenHash = Token::hashFor($request->tokenPlainValue, $this->appSecret);
        $signatureRequest = $this->repository->findByToken($tokenHash);

        if (null === $signatureRequest || $signatureRequest->isRemoved()) {
            throw new SignatureTokenInvalidException();
        }

        if ($signatureRequest->expiresAt() < new \DateTimeImmutable('NOW')
            || SignatureRequestStatus::Expired === $signatureRequest->status()) {
            throw new SignatureTokenInvalidException();
        }

        $signer = $signatureRequest->signer();
        $verified = $signatureRequest->verifyOtp($request->otpCode, $this->appSecret, $request->signerIp);
        $challenge = $signatureRequest->otpChallenge();

        $this->repository->add($signatureRequest);

        $channel = $signer->notificationChannel();
        $masked = NotificationChannel::Sms === $channel
            ? $this->maskPhone($signer->phone() ?? '')
            : $this->maskEmail($signer->email() ?? '');

        return new VerifyOtpResponse(
            verified: $verified,
            locked: $challenge?->isLocked() ?? false,
            attemptsLeft: $challenge?->attemptsLeft() ?? 0,
            notificationChannel: $channel,
            maskedDestination: $masked,
        );
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        $masked = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

        return $masked . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        return str_repeat('*', max(0, $len - 3)) . substr($phone, -3);
    }
}
