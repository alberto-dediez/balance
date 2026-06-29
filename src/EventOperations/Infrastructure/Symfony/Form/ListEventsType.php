<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form;

use App\EventOperations\Application\Event\List\ListEventsRequest;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSectorType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSizeType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventTypologyType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListEventsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('location', DestinationIdType::class, [
                'label' => new TranslatableMessage('Ubicación'),
                'required' => false,
            ])
            ->add('size', EventSizeType::class, [
                'label' => new TranslatableMessage('Tamaño del evento'),
                'required' => false,
            ])
            ->add('sector', EventSectorType::class, [
                'label' => new TranslatableMessage('Sector del evento'),
                'required' => false,
            ])
            ->add('typology', EventTypologyType::class, [
                'label' => new TranslatableMessage('Tipología del evento'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListEventsRequest::class,
        ]);
    }
}
