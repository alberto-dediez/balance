<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\CriticalPoint\Register\RegisterCriticalPointRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\SupplyChainStageType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class RegisterCriticalPointType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Centro'),
                'required' => true,
            ])
            ->add('supplyChainStage', SupplyChainStageType::class, [
                'label' => new TranslatableMessage('Etapa de la cadena de suministro'),
                'required' => true,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterCriticalPointRequest::class,
        ]);
    }
}
