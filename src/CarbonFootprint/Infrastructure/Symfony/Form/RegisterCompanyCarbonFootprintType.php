<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Register\RegisterCompanyCarbonFootprintRequest;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterCompanyCarbonFootprintType extends AbstractType
{
    public function __construct(private readonly LoggedAccount $loggedAccount) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = ($this->loggedAccount)();

        $currentYear = (int) date('Y');

        $builder
            ->add('companyId', CompanyIdType::class, [
                'label' => new TranslatableMessage('Empresa'),
                'account_id' => $account?->id(),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona una empresa'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('year', ChoiceType::class, [
                'label' => new TranslatableMessage('Año de cálculo'),
                'required' => true,
                'choices' => array_combine(range($currentYear, $currentYear - 9), range($currentYear, $currentYear - 9)),
                'data' => $currentYear - 1,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(min: 2000, max: $currentYear),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterCompanyCarbonFootprintRequest::class,
        ]);
    }
}
