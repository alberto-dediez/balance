<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRegistered;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
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
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmployeeContributionNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private SystemNotifier $notifier,
        private TranslatorInterface $translator,
        private EmployeeContributionRepository $employeeContributionRepository,
        private EmployeeRepository $employeeRepository,
        private UserRepository $userRepository,
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

        if ($event instanceof EmployeeContributionRegistered) {
            $employee = $this->employeeRepository->find($event->employeeId());

            if (!$employee instanceof Employee) {
                return;
            }

            $this->notify($event->token(), $employee);

            return;
        }

        assert($event instanceof EmployeeContributionNotificationResent);

        $employeeContribution = $this->employeeContributionRepository->find($event->employeeContributionId());

        if (!$employeeContribution instanceof EmployeeContribution) {
            return;
        }

        $employeeId = $employeeContribution->employeeId();
        $employee = null !== $employeeId ? $this->employeeRepository->find($employeeId) : null;

        if (!$employee instanceof Employee) {
            return;
        }

        $this->notify($employeeContribution->token(), $employee);
    }

    private function notify(#[\SensitiveParameter] string $token, Employee $employee): void
    {
        $userId = $employee->userId();
        $user = $userId instanceof UserId ? $this->userRepository->find($userId) : null;

        $preferredLocale = $user instanceof User ? $user->preferredLocale() : null;
        $locale = $preferredLocale instanceof Locale ? $preferredLocale->value : $this->localeSwitcher->getLocale();

        $employeeEmail = $employee->email();
        $recipient = !empty($employeeEmail)
            ? new EmailRecipient(name: $employee->name(), email: $employeeEmail)
            : ($user instanceof User ? new EmailRecipient(name: $user->username(), email: $user->username()) : null);

        if (null !== $recipient) {
            $this->localeSwitcher->runWithLocale($locale, function () use ($recipient, $employee, $token): void {
                $this->emailSender->__invoke(
                    recipient: $recipient,
                    subject: $this->translator->trans('Aporta tus datos de movilidad y pernoctaciones para el evento'),
                    template: '@carbon_footprint/emails/employee_contribution_requested.html.twig',
                    params: [
                        'employeeName' => $employee->name(),
                        'token' => $token,
                        'brandingTokens' => $this->brandingResolver->resolve($employee->accountId()),
                    ],
                );
            });
        }

        if (!$user instanceof User) {
            return;
        }

        $this->notifier->notify(new SystemNotification(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            subject: (string) new TranslatableMessage('Aporta tus datos de movilidad y pernoctaciones para el evento'),
            template: '@carbon_footprint/notification/employee-contribution-requested.notification.html.twig',
            params: [
                'employeeName' => $employee->name(),
                'token' => $token,
            ],
            accountId: $employee->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EmployeeContributionRegistered || $event instanceof EmployeeContributionNotificationResent;
    }
}
