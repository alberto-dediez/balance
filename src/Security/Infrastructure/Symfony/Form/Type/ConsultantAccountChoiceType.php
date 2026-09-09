<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ConsultantAccountChoiceType extends AbstractType
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->accountRepository->findByFilter(new AccountFilter(consultant: true, limit: 0)) as $account) {
            $choices[$this->label($account, $choices)] = (string) $account->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(Account $account, array $choices): string
    {
        $label = (string) $account;
        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
