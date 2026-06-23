<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class SelfSignTrainingAttendanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Firmar asistencia'),
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }
}
