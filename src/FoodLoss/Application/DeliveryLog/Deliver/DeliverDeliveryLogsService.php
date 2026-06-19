<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Deliver;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogsPdfGenerator;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class DeliverDeliveryLogsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DeliveryLogRepository $deliveryLogRepository,
        private RecipientOrganizationRepository $recipientOrganizationRepository,
        private DeliveryLogAuthorizer $authorizer,
        private DeliveryLogsPdfGenerator $deliveryLogsPdfGenerator,
        private RegisterSignatureRequestService $registerSignatureRequestService,
    ) {}

    public function __invoke(DeliverDeliveryLogsRequest $request, User $user): DeliverDeliveryLogsResponse
    {
        $deliveryLogs = $this->deliveryLogRepository->findByIds($request->deliveryLogIds);

        foreach ($deliveryLogs as $deliveryLog) {
            $this->authorizer->denyUnlessCanDoThat($deliveryLog, SecurityAction::Edit, $user);
        }

        if ($request->withSignature) {
            return $this->deliverWithSignature($request, $deliveryLogs, $user);
        }

        return $this->deliverDirectly($request, $deliveryLogs);
    }

    /**
     * @param DeliveryLog[] $deliveryLogs
     */
    private function deliverDirectly(DeliverDeliveryLogsRequest $request, array $deliveryLogs): DeliverDeliveryLogsResponse
    {
        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->deliver($request->deliveredAt, $request->pickupType);
            $this->deliveryLogRepository->add($deliveryLog);
        }

        return new DeliverDeliveryLogsResponse(deliveredCount: count($deliveryLogs));
    }

    /**
     * @param DeliveryLog[] $deliveryLogs
     */
    private function deliverWithSignature(
        DeliverDeliveryLogsRequest $request,
        array $deliveryLogs,
        User $user,
    ): DeliverDeliveryLogsResponse {
        $recipientOrganization = $this->resolveRecipientOrganization($deliveryLogs);

        $contact = $recipientOrganization->contact();

        $notificationChannel = $contact->preferredNotificationChannel();

        $this->validateContactData($notificationChannel, $contact->name(), $contact->email(), $contact->phone());

        $pdfPath = $this->deliveryLogsPdfGenerator->generate($deliveryLogs, $request->deliveredAt, $request->pickupType);

        $signerLocale = $contact->preferredLocale() ?? LocaleGetter::locale();

        $signatureRequest = new RegisterSignatureRequestRequest(
            documentToSignPath: $pdfPath,
            signerName: $contact->name(),
            signerLocale: $signerLocale,
            signerEmail: $contact->email() ?: null,
            signerPhone: $contact->phone() ?: null,
            notificationChannel: $notificationChannel,
            accountId: $request->accountId,
            otpRequired: $request->otpRequired,
        );

        $signatureResponse = ($this->registerSignatureRequestService)($signatureRequest, $user);

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->startSignatureProcess($signatureResponse->signatureRequestId, $request->deliveredAt, $request->pickupType);
            $this->deliveryLogRepository->add($deliveryLog);
        }

        return new DeliverDeliveryLogsResponse(
            deliveredCount: count($deliveryLogs),
            signatureRequestId: $signatureResponse->signatureRequestId,
        );
    }

    /**
     * @param DeliveryLog[] $deliveryLogs
     */
    private function resolveRecipientOrganization(array $deliveryLogs): RecipientOrganization
    {
        $recipientOrganizationIds = array_unique(
            array_map(fn(DeliveryLog $log) => (string) $log->recipientOrganizationId(), $deliveryLogs),
        );

        if (count($recipientOrganizationIds) > 1) {
            throw MixedRecipientOrganizationsException::forSignatureRequest();
        }

        $firstLog = current($deliveryLogs);
        if (false === $firstLog) {
            throw SignerDataMissingException::missingName();
        }

        $recipientOrganizationId = $firstLog->recipientOrganizationId();

        if (null === $recipientOrganizationId) {
            throw SignerDataMissingException::missingName();
        }

        $recipientOrganization = $this->recipientOrganizationRepository->find($recipientOrganizationId);

        if (null === $recipientOrganization) {
            throw SignerDataMissingException::missingName();
        }

        return $recipientOrganization;
    }

    private function validateContactData(NotificationChannel $channel, string $name, string $email, string $phone): void
    {
        if (empty($name)) {
            throw SignerDataMissingException::missingName();
        }

        if (NotificationChannel::Email === $channel && empty($email)) {
            throw SignerDataMissingException::missingEmailForEmailChannel();
        }

        if (in_array($channel, [NotificationChannel::Sms, NotificationChannel::Whatsapp], true) && empty($phone)) {
            throw SignerDataMissingException::missingPhoneForSmsChannel();
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeliverDeliveryLogsRequest;
    }
}
