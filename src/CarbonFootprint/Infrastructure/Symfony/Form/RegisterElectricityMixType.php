<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ElectricityMix\Register\RegisterElectricityMixRequest;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterElectricityMixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona un país'),
                'constraints' => [
                    new Assert\NotNull(),
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
            'data_class' => RegisterElectricityMixRequest::class,
        ]);
    }
}
