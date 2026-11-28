<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ContestEntry\List\ListContestEntriesRequest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListContestEntriesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => new TranslatableMessage('Nombre o email'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'class' => ContestEntryStatus::class,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => static fn(ContestEntryStatus $status): TranslatableMessage => new TranslatableMessage($status->value, [], 'contestEntryStatus'),
            ])
            ->add('winner', BooleanType::class, [
                'label' => new TranslatableMessage('Ganador'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListContestEntriesRequest::class,
            'empty_data' => null,
        ]);
    }
}
