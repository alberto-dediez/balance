<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest\RegisterContestEntryFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<RegisterContestEntryFormData> */
class RegisterContestEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre y apellidos'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            ])
            ->add('phone', TelType::class, [
                'label' => new TranslatableMessage('Teléfono'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 40)],
            ])
            ->add('country', ChoiceType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Elige tu país'),
                'choices' => array_flip($options['countries']),
                'choice_translation_domain' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
        ;

        if (true === $options['adults_only']) {
            $builder->add('adultConfirmed', CheckboxType::class, [
                'label' => new TranslatableMessage('Confirmo que soy mayor de edad'),
                'required' => true,
            ]);
        }

        $builder
            ->add('consent', CheckboxType::class, [
                'label' => $options['consent_label'],
                'label_html' => true,
                'required' => true,
            ])
            ->add('enter', SubmitType::class, [
                'label' => new TranslatableMessage('Participar en el concurso'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterContestEntryFormData::class,
            'countries' => [],
            'adults_only' => false,
            'consent_label' => '',
        ]);

        $resolver->setAllowedTypes('countries', 'array');
        $resolver->setAllowedTypes('adults_only', 'bool');
        $resolver->setAllowedTypes('consent_label', 'string');
    }
}
