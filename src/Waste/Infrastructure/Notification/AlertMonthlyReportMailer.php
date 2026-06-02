<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Notification;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Waste\Application\Alert\MonthlyReport\MonthlyReportResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlertMonthlyReportMailer
{
    public function __construct(
        private readonly EmailSender $emailSender,
        private readonly UserRepository $userRepository,
        private readonly AccountRepository $accountRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function send(MonthlyReportResponse $response, \DateTimeImmutable $month): void
    {
        foreach ($response->alertsByAccount as $accountIdString => $alerts) {
            $accountId = new AccountId($accountIdString);
            $account = $this->accountRepository->find($accountId);

            if (null === $account) {
                continue;
            }

            $users = $this->userRepository->findByAccountId($accountId);

            $owners = array_filter($users, fn($user) => $user->hasRole(Role::Owner));

            if (0 === count($owners)) {
                continue;
            }

            foreach ($owners as $owner) {
                $this->emailSender->__invoke(
                    recipient: new EmailRecipient(name: $owner->username(), email: $owner->username()),
                    subject: $this->translator->trans(
                        'Resumen mensual de alertas: {month}',
                        ['{month}' => $month->format('Y-m')],
                    ),
                    template: '@waste/notification/alerts-monthly-report.email.html.twig',
                    params: [
                        'account' => $account,
                        'alerts' => $alerts,
                        'month' => $month,
                    ],
                );
            }
        }
    }
}
