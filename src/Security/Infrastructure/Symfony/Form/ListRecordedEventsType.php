<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Infrastructure\Symfony\Form\Type\UserIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListRecordedEventsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventName', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('context', TextType::class, [
                'label' => new TranslatableMessage('Contexto'),
                'required' => false,
            ])
            ->add('userId', UserIdType::class, [
                'label' => new TranslatableMessage('Usuario'),
                'required' => false,
            ])
            ->add('impersonatorId', UserIdType::class, [
                'label' => new TranslatableMessage('Suplantado por'),
                'required' => false,
            ])
            ->add('aggregateRootId', TextType::class, [
                'label' => new TranslatableMessage('Entidad'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListRecordedEventsRequest::class,
        ]);
    }
}
