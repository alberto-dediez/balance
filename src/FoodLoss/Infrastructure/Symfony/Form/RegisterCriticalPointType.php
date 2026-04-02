<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\CriticalPoint\Register\RegisterCriticalPointRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PriorityAssessmentType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ServiceCategoryIdType;
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
            ->add('prevention', PriorityAssessmentType::class, [
                'label' => new TranslatableMessage('¿Se podía haber prevenido el desperdicio?'),
                'action_priority' => \App\FoodLoss\Domain\Model\Action\Priority::Prevention,
            ])
            ->add('humanDonation', PriorityAssessmentType::class, [
                'label' => new TranslatableMessage('¿Se puede donar?'),
                'action_priority' => \App\FoodLoss\Domain\Model\Action\Priority::HumanDonation,
            ])
            ->add('animalFeed', PriorityAssessmentType::class, [
                'label' => new TranslatableMessage('¿Se puede usar para alimentación animal?'),
                'action_priority' => \App\FoodLoss\Domain\Model\Action\Priority::AnimalFeed,
            ])
            ->add('transformation', PriorityAssessmentType::class, [
                'label' => new TranslatableMessage('¿Se puede transformar para consumo humano?'),
                'action_priority' => \App\FoodLoss\Domain\Model\Action\Priority::Transformation,
            ])
            ->add('composting', PriorityAssessmentType::class, [
                'label' => new TranslatableMessage('¿Se puede dar un uso no humano (ej. compostaje)?'),
                'action_priority' => \App\FoodLoss\Domain\Model\Action\Priority::Composting,
            ])
            ->add('serviceCategoryIds', ServiceCategoryIdType::class, [
                'label' => new TranslatableMessage('Categorías de servicio'),
                'required' => false,
                'multiple' => true,
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
