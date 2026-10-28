<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrganizationalProviderContributionNotifiedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private TranslatorInterface $translator,
        private OrganizationalProviderContributionRepository $contributionRepository,
        private ProviderRepository $providerRepository,
        private OrganizationalCarbonFootprintLocator $locator,
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

        if ($event instanceof OrganizationalProviderContributionRegistered) {
            if (!$event->mode()->isRequested()) {
                return;
            }

            $this->notify($event->token(), $event->providerId(), $event->subjectType(), $event->carbonFootprintId(), $event->companyName());

            return;
        }

        assert($event instanceof OrganizationalProviderContributionNotificationResent);

        $contribution = $this->contributionRepository->find($event->contributionId());

        if (!$contribution instanceof OrganizationalProviderContribution) {
            return;
        }

        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            return;
        }

        $this->notify($contribution->token(), $providerId, $contribution->subjectType(), $contribution->carbonFootprintId(), $contribution->companyName());
    }

    private function notify(
        #[\SensitiveParameter]
        string $token,
        ProviderId $providerId,
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ?string $companyName,
    ): void {
        $provider = $this->providerRepository->find($providerId);

        if (!$provider instanceof Provider || empty($provider->contact()->email())) {
            return;
        }

        try {
            $carbonFootprint = $this->locator->locate($subjectType, $carbonFootprintId)->carbonFootprint;
        } catch (\InvalidArgumentException) {
            return;
        }

        $subjectName = $this->subjectName($carbonFootprint);
        $year = $carbonFootprint->year();

        $contact = $provider->contact();

        $preferredLocale = $contact->preferredLocale();
        $locale = $preferredLocale instanceof Locale ? $preferredLocale->value : $this->localeSwitcher->getLocale();

        $contactName = $contact->name();

        $this->localeSwitcher->runWithLocale($locale, function () use ($provider, $contact, $contactName, $companyName, $token, $subjectName, $year): void {
            $this->emailSender->__invoke(
                recipient: new EmailRecipient(name: $contactName ?? $companyName ?? $contact->email(), email: $contact->email()),
                subject: $this->translator->trans('Aporta tus datos de emisiones de {year} para {subject}', ['{year}' => (string) $year, '{subject}' => $subjectName]),
                template: '@carbon_footprint/emails/organizational_provider_contribution_requested.html.twig',
                params: [
                    'contactName' => $contactName,
                    'companyName' => $companyName,
                    'subjectName' => $subjectName,
                    'year' => $year,
                    'token' => $token,
                    'brandingTokens' => $this->brandingResolver->resolve($provider->accountId()),
                ],
            );
        });
    }

    private function subjectName(PropertyCarbonFootprint|CompanyCarbonFootprint $carbonFootprint): string
    {
        if ($carbonFootprint instanceof CompanyCarbonFootprint) {
            $companyId = $carbonFootprint->companyId();

            return (null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null) ?? '';
        }

        $propertyId = $carbonFootprint->propertyId();

        return (null !== $propertyId ? $this->propertyRepository->find($propertyId)?->name() : null) ?? '';
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof OrganizationalProviderContributionRegistered || $event instanceof OrganizationalProviderContributionNotificationResent;
    }
}
