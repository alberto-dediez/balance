<?php

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Infrastructure\UI\Web\DTO\ImportFileRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportFileType extends AbstractType
{
    private const MAX_FILE_SIZE = '4096k';

    private const VALID_MIME_TYPES = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => new TranslatableMessage('Fichero a importar'),
                'required' => true,
                'constraints' => [
                    new NotNull([
                        'message' => new TranslatableMessage('Por favor, selecciona un fichero.'),
                    ]),
                    new File([
                        'maxSize' => self::MAX_FILE_SIZE,
                        'maxSizeMessage' => new TranslatableMessage('El fichero es demasiado grande ({{ size }} {{ suffix }}). El tamaño máximo permitido es {{ limit }} {{ suffix }}.'),
                        'mimeTypes' => self::VALID_MIME_TYPES,
                        'mimeTypesMessage' => new TranslatableMessage('Por favor, sube un fichero CSV o Excel válido.'),
                    ]),
                ],
            ])
            ->add('offset', NumberType::class, [
                'label' => new TranslatableMessage('Líneas a ignorar del inicio del fichero'),
                'required' => true
            ])
            ->add('import', SubmitType::class, [
                'label' => new TranslatableMessage('Importar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportFileRequest::class,
        ]);
    }
}
