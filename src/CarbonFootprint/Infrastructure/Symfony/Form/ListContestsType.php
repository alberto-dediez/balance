<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Contest\List\ListContestsRequest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListContestsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'class' => ContestStatus::class,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => static fn(ContestStatus $status): TranslatableMessage => new TranslatableMessage($status->value, [], 'contestStatus'),
            ])
            ->add('participationMode', EnumType::class, [
                'label' => new TranslatableMessage('Participación'),
                'required' => false,
                'class' => ContestParticipationMode::class,
                'placeholder' => new TranslatableMessage('Todas'),
                'choice_label' => static fn(ContestParticipationMode $mode): TranslatableMessage => new TranslatableMessage($mode->value, [], 'contestParticipationMode'),
            ])
            ->add('awardMode', EnumType::class, [
                'label' => new TranslatableMessage('Premio'),
                'required' => false,
                'class' => ContestAwardMode::class,
                'placeholder' => new TranslatableMessage('Todos'),
                'choice_label' => static fn(ContestAwardMode $mode): TranslatableMessage => new TranslatableMessage($mode->value, [], 'contestAwardMode'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListContestsRequest::class,
        ]);
    }
}
