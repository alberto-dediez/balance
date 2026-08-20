<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRegistered;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderContributionNotifiedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private TranslatorInterface $translator,
        private ProviderContributionRepository $providerContributionRepository,
        private ProviderRepository $providerRepository,
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

        if ($event instanceof ProviderContributionRegistered) {
            $provider = $this->providerRepository->find($event->providerId());

            if (!$provider instanceof Provider) {
                return;
            }

            $this->notify($event->token(), $provider, $event->companyName());

            return;
        }

        assert($event instanceof ProviderContributionNotificationResent);

        $providerContribution = $this->providerContributionRepository->find($event->providerContributionId());

        if (!$providerContribution instanceof ProviderContribution) {
            return;
        }

        $providerId = $providerContribution->providerId();
        $provider = null !== $providerId ? $this->providerRepository->find($providerId) : null;

        if (!$provider instanceof Provider || empty($provider->contact()->email())) {
            return;
        }

        $this->notify($providerContribution->token(), $provider, $providerContribution->companyName());
    }

    private function notify(#[\SensitiveParameter] string $token, Provider $provider, ?string $companyName): void
    {
        $contact = $provider->contact();

        $preferredLocale = $contact->preferredLocale();
        $locale = $preferredLocale instanceof Locale ? $preferredLocale->value : $this->localeSwitcher->getLocale();

        $contactName = $contact->name();

        $this->localeSwitcher->runWithLocale($locale, function () use ($provider, $contact, $contactName, $companyName, $token): void {
            $this->emailSender->__invoke(
                recipient: new EmailRecipient(name: $contactName ?? $companyName ?? $contact->email(), email: $contact->email()),
                subject: $this->translator->trans('Aporta tus datos de emisiones para el evento'),
                template: '@carbon_footprint/emails/provider_contribution_requested.html.twig',
                params: [
                    'contactName' => $contactName,
                    'companyName' => $companyName,
                    'token' => $token,
                    'brandingTokens' => $this->brandingResolver->resolve($provider->accountId()),
                ],
            );
        });
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProviderContributionRegistered || $event instanceof ProviderContributionNotificationResent;
    }
}
