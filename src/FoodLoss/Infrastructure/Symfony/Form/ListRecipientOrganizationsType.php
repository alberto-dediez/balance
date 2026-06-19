<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\RecipientOrganization\List\ListRecipientOrganizationsRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PickupTypeType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListRecipientOrganizationsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('nif', TextType::class, [
                'label' => new TranslatableMessage('NIF'),
                'required' => false,
            ])
            ->add('socialReason', TextType::class, [
                'label' => new TranslatableMessage('Razón social'),
                'required' => false,
            ])
            ->add('contact', TextType::class, [
                'label' => new TranslatableMessage('Contacto'),
                'required' => false,
            ])
            ->add('pickupType', PickupTypeType::class, [
                'label' => new TranslatableMessage('Tipo de recogida'),
                'required' => false,
                'multiple' => false,
                'expanded' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListRecipientOrganizationsRequest::class,
        ]);
    }
}
