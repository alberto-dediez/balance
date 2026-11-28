<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class PublishContestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('responsibilityAccepted', CheckboxType::class, [
                'label' => new TranslatableMessage('Declaro que soy el organizador y único responsable de este concurso, que dispongo de bases legales y que cumplo la normativa aplicable de juego, consumo, fiscalidad y protección de datos.'),
                'required' => true,
            ])
            ->add('publish', SubmitType::class, [
                'label' => new TranslatableMessage('Publicar'),
            ])
        ;
    }
}
