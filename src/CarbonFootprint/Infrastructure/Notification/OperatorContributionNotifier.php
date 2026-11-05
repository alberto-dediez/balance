<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRegistered;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class OperatorContributionNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private TranslatorInterface $translator,
        private OperatorContributionRepository $contributionRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private LocaleSwitcher $localeSwitcher,
        private BrandingResolver $brandingResolver,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        if ($event instanceof OperatorContributionRegistered) {
            $this->notify(
                token: $event->token(),
                contactEmail: $event->contactEmail(),
                contactName: $event->contactName(),
                contactLocale: $event->contactLocale(),
                companyCarbonFootprintId: $event->companyCarbonFootprintId(),
                propertyId: $event->propertyId(),
                accountId: $event->accountId(),
            );

            return;
        }

        assert($event instanceof OperatorContributionNotificationResent);

        $contribution = $this->contributionRepository->find($event->contributionId());

        if (!$contribution instanceof OperatorContribution) {
            return;
        }

        $this->notify(
            token: $contribution->token(),
            contactEmail: $contribution->contactEmail(),
            contactName: $contribution->contactName(),
            contactLocale: $contribution->contactLocale(),
            companyCarbonFootprintId: $contribution->companyCarbonFootprintId(),
            propertyId: $contribution->propertyId(),
            accountId: $contribution->accountId(),
        );
    }

    private function notify(
        #[\SensitiveParameter]
        string $token,
        string $contactEmail,
        ?string $contactName,
        Locale $contactLocale,
        ?CompanyCarbonFootprintId $companyCarbonFootprintId,
        ?PropertyId $propertyId,
        ?AccountId $accountId,
    ): void {
        $companyCarbonFootprint = $companyCarbonFootprintId instanceof CompanyCarbonFootprintId ? $this->companyCarbonFootprintRepository->find($companyCarbonFootprintId) : null;

        if (null === $companyCarbonFootprint) {
            return;
        }

        $companyId = $companyCarbonFootprint->companyId();

        $params = [
            'contactName' => $contactName,
            'propertyName' => ($propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId)?->name() : null) ?? '',
            'companyName' => ($companyId instanceof CompanyId ? $this->companyRepository->find($companyId)?->name() : null) ?? '',
            'year' => $companyCarbonFootprint->year(),
            'token' => $token,
        ];

        $recipient = new EmailRecipient(name: $contactName ?? $contactEmail, email: $contactEmail);

        $this->localeSwitcher->runWithLocale($contactLocale->value, function () use ($recipient, $params, $accountId): void {
            $this->emailSender->__invoke(
                recipient: $recipient,
                subject: $this->translator->trans('Aporta los consumos de {year} de {property} para la huella de {company}', ['{year}' => (string) $params['year'], '{property}' => $params['propertyName'], '{company}' => $params['companyName']]),
                template: '@carbon_footprint/emails/operator_contribution_requested.html.twig',
                params: $params + ['brandingTokens' => $this->brandingResolver->resolve($accountId)],
            );
        });
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof OperatorContributionRegistered || $event instanceof OperatorContributionNotificationResent;
    }
}
