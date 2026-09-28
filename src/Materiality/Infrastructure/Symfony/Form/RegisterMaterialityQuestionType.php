<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityAnswerScaleType;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityCriterionType;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityTopicChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterMaterialityQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('criterion', MaterialityCriterionType::class, [
                'label' => new TranslatableMessage('Criterio'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('topicId', MaterialityTopicChoiceType::class, [
                'label' => new TranslatableMessage('Categoría'),
            ])
            ->add('text', TextareaType::class, [
                'label' => new TranslatableMessage('Pregunta'),
                'help' => new TranslatableMessage('Redacta la pregunta de modo que «Alto» o «Sí» signifique mayor relevancia de la categoría'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('help', TextareaType::class, [
                'label' => new TranslatableMessage('Ayuda'),
                'required' => false,
                'empty_data' => '',
            ])
            ->add('answerScale', MaterialityAnswerScaleType::class, [
                'label' => new TranslatableMessage('Escala de respuesta'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('weight', IntegerType::class, [
                'label' => new TranslatableMessage('Peso'),
                'required' => true,
                'constraints' => [new Assert\Range(min: 1)],
            ])
            ->add('position', IntegerType::class, [
                'label' => new TranslatableMessage('Posición'),
                'required' => true,
                'constraints' => [new Assert\Range(min: 0)],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterMaterialityQuestionRequest::class,
        ]);
    }
}
