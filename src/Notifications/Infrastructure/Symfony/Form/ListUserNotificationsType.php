<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Symfony\Form;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListUserNotificationsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('onlyUnread', BooleanType::class, [
                'label' => new TranslatableMessage('Solo no leídas'),
                'placeholder' => false,
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListNotificationsRequest::class,
        ]);
    }
}
