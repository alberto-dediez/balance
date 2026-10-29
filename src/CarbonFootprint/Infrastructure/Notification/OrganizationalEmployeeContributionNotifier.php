<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrganizationalEmployeeContributionNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private SystemNotifier $notifier,
        private TranslatorInterface $translator,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private EmployeeRepository $employeeRepository,
        private UserRepository $userRepository,
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

        if ($event instanceof OrganizationalEmployeeContributionRegistered) {
            $employee = $this->employeeRepository->find($event->employeeId());

            if (!$employee instanceof Employee) {
                return;
            }

            $this->notify($event->token(), $employee, $event->subjectType(), $event->carbonFootprintId());

            return;
        }

        assert($event instanceof OrganizationalEmployeeContributionNotificationResent);

        $contribution = $this->contributionRepository->find($event->contributionId());

        if (!$contribution instanceof OrganizationalEmployeeContribution) {
            return;
        }

        $employeeId = $contribution->employeeId();
        $employee = null !== $employeeId ? $this->employeeRepository->find($employeeId) : null;

        if (!$employee instanceof Employee) {
            return;
        }

        $this->notify($contribution->token(), $employee, $contribution->subjectType(), $contribution->carbonFootprintId());
    }

    private function notify(#[\SensitiveParameter] string $token, Employee $employee, CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): void
    {
        $subject = $this->subjectOf($subjectType, $carbonFootprintId);

        if (null === $subject) {
            return;
        }

        $userId = $employee->userId();
        $user = $userId instanceof UserId ? $this->userRepository->find($userId) : null;

        $preferredLocale = $user instanceof User ? $user->preferredLocale() : null;
        $locale = $preferredLocale instanceof Locale ? $preferredLocale->value : $this->localeSwitcher->getLocale();

        $employeeEmail = $employee->email();
        $recipient = !empty($employeeEmail)
            ? new EmailRecipient(name: $employee->name(), email: $employeeEmail)
            : ($user instanceof User ? new EmailRecipient(name: $user->username(), email: $user->username()) : null);

        $params = [
            'employeeName' => $employee->name(),
            'subjectName' => $subject['name'],
            'year' => $subject['year'],
            'token' => $token,
        ];

        if (null !== $recipient) {
            $this->localeSwitcher->runWithLocale($locale, function () use ($recipient, $employee, $params): void {
                $this->emailSender->__invoke(
                    recipient: $recipient,
                    subject: $this->translator->trans('Aporta tus desplazamientos y viajes de {year} para {subject}', ['{year}' => $params['year'], '{subject}' => $params['subjectName']]),
                    template: '@carbon_footprint/emails/organizational_employee_contribution_requested.html.twig',
                    params: $params + ['brandingTokens' => $this->brandingResolver->resolve($employee->accountId())],
                );
            });
        }

        if (!$user instanceof User) {
            return;
        }

        $this->notifier->notify(new SystemNotification(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            subject: $this->translator->trans('Aporta tus desplazamientos y viajes de {year} para {subject}', ['{year}' => $params['year'], '{subject}' => $params['subjectName']]),
            template: '@carbon_footprint/notification/organizational-employee-contribution-requested.notification.html.twig',
            params: $params,
            accountId: $employee->accountId(),
        ));
    }

    /** @return ?array{name: string, year: int} */
    private function subjectOf(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?array
    {
        try {
            $carbonFootprint = $this->locator->locate($subjectType, $carbonFootprintId)->carbonFootprint;
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($carbonFootprint instanceof CompanyCarbonFootprint) {
            $companyId = $carbonFootprint->companyId();
            $name = null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null;
        } else {
            $propertyId = $carbonFootprint->propertyId();
            $name = null !== $propertyId ? $this->propertyRepository->find($propertyId)?->name() : null;
        }

        return ['name' => $name ?? '', 'year' => $carbonFootprint->year()];
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof OrganizationalEmployeeContributionRegistered || $event instanceof OrganizationalEmployeeContributionNotificationResent;
    }
}
