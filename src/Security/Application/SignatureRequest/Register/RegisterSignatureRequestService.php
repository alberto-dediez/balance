<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Register;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\Signer;
use App\Security\Domain\Model\SignatureRequest\SmsChannelNotEnabledException;
use App\Security\Domain\Model\SignatureRequest\Token;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterSignatureRequestService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SignatureRequestRepository $signatureRequestRepository,
        private SignatureRequestAuthorizer $authorizer,
        #[\SensitiveParameter]
        private string $appSecret = '',
        private bool $smsEnabled = false,
    ) {}

    public function __invoke(RegisterSignatureRequestRequest $request, User $user): RegisterSignatureRequestResponse
    {
        $id = SignatureRequestId::generate();

        $this->authorizer->denyUnlessCanDoThat(new SignatureRequest(id: $id), SecurityAction::Register, $user);

        if (!$this->smsEnabled && NotificationChannel::Sms === $request->notificationChannel) {
            throw new SmsChannelNotEnabledException();
        }

        $token = Token::generate($this->appSecret);
        $documentToSign = DocumentReference::fromPath($request->documentToSignPath);
        $signer = new Signer(
            name: $request->signerName,
            locale: $request->signerLocale,
            email: $request->signerEmail,
            phone: $request->signerPhone,
            notificationChannel: $request->notificationChannel,
        );

        $signatureRequest = new SignatureRequest($id);
        $signatureRequest->register(
            token: $token,
            documentToSign: $documentToSign,
            signer: $signer,
            accountId: $request->accountId,
        );

        $this->signatureRequestRepository->add($signatureRequest);

        return new RegisterSignatureRequestResponse(
            signatureRequestId: $signatureRequest->id(),
            tokenPlainValue: $token->plainValue(),
            status: $signatureRequest->status(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterSignatureRequestRequest;
    }
}
