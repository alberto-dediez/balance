<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly EmployeeRepository $employeeRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $employees = $this->employeeRepository->findByFilter(
            new EmployeeFilter(
                accountId: $account instanceof Account ? $account->id() : null,
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($employees),
            'class' => EmployeeId::class,
            'multiple' => false,
            'required' => false,
        ]);
    }
}
