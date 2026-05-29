<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\SendOtp;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Security\Domain\Model\SignatureRequest\Token;

class SendOtpService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(SendOtpRequest $request): SendOtpResponse
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

        if ($signatureRequest->isOtpVerified()) {
            return new SendOtpResponse(
                signatureRequestId: $signatureRequest->id(),
                notificationChannel: $signer->notificationChannel(),
                maskedDestination: $this->maskedDestination($signer->notificationChannel(), $signer->email(), $signer->phone()),
                alreadyVerified: true,
            );
        }

        $existingChallenge = $signatureRequest->otpChallenge();
        $needsNew = null === $existingChallenge
            || $existingChallenge->isExpired()
            || $existingChallenge->isLocked();

        if ($needsNew) {
            $challenge = OtpChallenge::generate($this->appSecret);
            $signatureRequest->sendOtp($challenge);
            $this->repository->add($signatureRequest);
        }

        return new SendOtpResponse(
            signatureRequestId: $signatureRequest->id(),
            notificationChannel: $signer->notificationChannel(),
            maskedDestination: $this->maskedDestination($signer->notificationChannel(), $signer->email(), $signer->phone()),
            alreadyVerified: false,
        );
    }

    private function maskedDestination(NotificationChannel $channel, ?string $email, ?string $phone): string
    {
        if (NotificationChannel::Sms === $channel && null !== $phone) {
            $len = strlen($phone);

            return str_repeat('*', max(0, $len - 3)) . substr($phone, -3);
        }

        if (null !== $email) {
            $parts = explode('@', $email, 2);
            $local = $parts[0];
            $domain = $parts[1] ?? '';
            $masked = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

            return $masked . '@' . $domain;
        }

        return '';
    }
}
