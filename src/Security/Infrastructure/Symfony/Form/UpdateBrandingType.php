<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Security\Application\Branding\Update\UpdateBrandingRequest;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Security\Infrastructure\Symfony\Form\DataTransformer\FontFamilyNameTransformer;
use App\Security\Infrastructure\Symfony\Form\Type\HexColorType;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<UpdateBrandingRequest>
 */
class UpdateBrandingType extends AbstractType
{
    /** @var list<string> */
    public const array POPULAR_GOOGLE_FONTS = [
        'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway',
        'Nunito', 'Rubik', 'Work Sans', 'Source Sans 3', 'DM Sans', 'Manrope',
        'Playfair Display', 'Merriweather', 'Lora', 'IBM Plex Sans',
    ];

    public function __construct(private readonly ThemeRegistry $themeRegistry) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['with_theme']) {
            $choices = [];

            foreach ($this->themeRegistry->all() as $theme) {
                $choices[$theme->name()] = $theme->slug();
            }

            $builder->add('themeSlug', ChoiceType::class, [
                'label' => new TranslatableMessage('Tema visual'),
                'choices' => $choices,
                'data' => $options['theme_slug'],
                'mapped' => false,
                'required' => true,
            ]);
        }

        $builder
            ->add('logoMediaId', MediaPickerType::class, [
                'label' => new TranslatableMessage('Logotipo'),
                'required' => false,
                'types' => [MediaType::Image],
                'visibility' => MediaVisibility::Public,
            ])
            ->add('primary', HexColorType::class, [
                'label' => new TranslatableMessage('Color principal'),
                'required' => false,
            ])
            ->add('secondary', HexColorType::class, [
                'label' => new TranslatableMessage('Color secundario'),
                'required' => false,
            ])
            ->add('success', HexColorType::class, [
                'label' => new TranslatableMessage('Color de éxito'),
                'required' => false,
            ])
            ->add('info', HexColorType::class, [
                'label' => new TranslatableMessage('Color informativo'),
                'required' => false,
            ])
            ->add('warning', HexColorType::class, [
                'label' => new TranslatableMessage('Color de aviso'),
                'required' => false,
            ])
            ->add('danger', HexColorType::class, [
                'label' => new TranslatableMessage('Color de error'),
                'required' => false,
            ])
            ->add('light', HexColorType::class, [
                'label' => new TranslatableMessage('Color claro'),
                'required' => false,
            ])
            ->add('dark', HexColorType::class, [
                'label' => new TranslatableMessage('Color oscuro'),
                'required' => false,
            ])
            ->add('bodyColor', HexColorType::class, [
                'label' => new TranslatableMessage('Color del texto'),
                'required' => false,
            ])
            ->add('bodyBg', HexColorType::class, [
                'label' => new TranslatableMessage('Color de fondo'),
                'required' => false,
            ])
            ->add('primaryHover', HexColorType::class, [
                'label' => new TranslatableMessage('Principal al pasar el ratón'),
                'required' => false,
            ])
            ->add('primarySubtle', HexColorType::class, [
                'label' => new TranslatableMessage('Principal atenuado'),
                'required' => false,
            ])
            ->add('secondaryHover', HexColorType::class, [
                'label' => new TranslatableMessage('Secundario al pasar el ratón'),
                'required' => false,
            ])
            ->add('secondarySubtle', HexColorType::class, [
                'label' => new TranslatableMessage('Secundario atenuado'),
                'required' => false,
            ])
            ->add('webFontFamily', TextType::class, [
                'label' => new TranslatableMessage('Tipografía web'),
                'required' => false,
                'help' => new TranslatableMessage('Nombre de una familia de Google Fonts, por ejemplo "Poppins".'),
                'invalid_message' => 'Usa solo letras, números, espacios y guiones: no es un nombre de familia válido.',
            ])
            ->add('webFontWeights', TextType::class, [
                'label' => new TranslatableMessage('Grosores de la tipografía web'),
                'required' => false,
                'help' => new TranslatableMessage('Separados por punto y coma, por ejemplo "400;600;700".'),
                'constraints' => [
                    new Assert\Regex(pattern: '/^[0-9;]{1,50}$/', message: 'Usa solo números separados por punto y coma.'),
                ],
            ])
            ->add('pdfFontFamily', EnumType::class, [
                'label' => new TranslatableMessage('Tipografía del certificado PDF'),
                'class' => PdfFontFamily::class,
                'choice_label' => static fn(PdfFontFamily $family): string => $family->value,
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;

        $builder->get('webFontFamily')->addModelTransformer(new FontFamilyNameTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateBrandingRequest::class,
            'with_theme' => false,
            'theme_slug' => null,
        ]);

        $resolver->setAllowedTypes('with_theme', 'bool');
        $resolver->setAllowedTypes('theme_slug', ['null', 'string']);
    }
}
