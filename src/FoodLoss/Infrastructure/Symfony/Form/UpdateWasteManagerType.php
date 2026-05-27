<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\WasteManager\Update\UpdateWasteManagerRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateWasteManagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('animalFeedAuthorized', CheckboxType::class, [
                'label' => new TranslatableMessage('Autorizado para alimentación animal'),
                'required' => false,
            ])
            ->add('compostAuthorized', CheckboxType::class, [
                'label' => new TranslatableMessage('Autorizado para subproductos orgánicos (compost, biogás, etc.)'),
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateWasteManagerRequest::class,
        ]);
    }
}
