<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Payee;

use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;

final class AccountAndProjectPayeeSubjectDirectory implements PayeeSubjectDirectory
{
    public function __construct(
        private AccountRepository $accountRepository,
        private ProjectRepository $projectRepository,
    ) {}

    /** @return array<string, string> */
    public function subjects(PayeeType $type): array
    {
        $subjects = [];

        if (PayeeType::Account === $type) {
            foreach ($this->accountRepository->findByFilter(new AccountFilter(limit: 0)) as $account) {
                $subjects[(string) $account->id()] = $account->name();
            }

            return $subjects;
        }

        foreach ($this->projectRepository->findByFilter(new ProjectFilter(limit: 0)) as $project) {
            $subjects[(string) $project->id()] = $project->name();
        }

        return $subjects;
    }

    public function label(PayeeType $type, string $subjectId): ?string
    {
        return $this->subjects($type)[$subjectId] ?? null;
    }
}
