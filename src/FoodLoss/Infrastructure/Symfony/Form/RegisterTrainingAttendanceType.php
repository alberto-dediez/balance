<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\TrainingAttendance\Register\RegisterTrainingAttendanceRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\EmployeeIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<RegisterTrainingAttendanceRequest> */
class RegisterTrainingAttendanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attendedAt', DateType::class, [
                'label' => new TranslatableMessage('Fecha de asistencia'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('attendanceSheet', FileType::class, [
                'label' => new TranslatableMessage('Hoja de asistencia (PDF)'),
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\File([
                        'maxSize' => '16M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => new TranslatableMessage('Por favor, sube un fichero PDF válido.'),
                    ]),
                ],
            ])
            ->add('employeeIds', EmployeeIdType::class, [
                'label' => new TranslatableMessage('Empleados'),
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar asistencias'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterTrainingAttendanceRequest::class,
        ]);
    }
}
