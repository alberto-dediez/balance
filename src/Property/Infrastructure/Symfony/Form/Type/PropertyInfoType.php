<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use App\Property\Domain\Model\Brand\BrandId;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Domain\Model\Destination\Destination;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use App\Property\Infrastructure\Symfony\Form\Type\BrandIdType;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyTypeType;

class PropertyInfoType extends AbstractType
{
    public function __construct(private DestinationRepository $destinationRepository)
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $changePropertyInfo = function (PropertyInfo $info, array $changes) {
            $propertyType = $info->propertyType();
            $categoryId = $info->categoryId();
            $brandId = $info->brandId();
            $address = $info->address();
            $postalCode = $info->postalCode();
            $longitude = $info->longitude();
            $latitude = $info->latitude();
            $rooms = $info->rooms();
            $zone = $info->zone();
            $web = $info->web();
            $destinationId = $info->destinationId();
            $countryId = $info->countryId();

            foreach ($changes as $name => $value) {
                switch ($name) {
                    case 'categoryId':
                        $value = is_string($value) && !empty($value) ? new CategoryId($value) : $value;
                        break;
                    case 'brandId':
                        $value = is_string($value) && !empty($value) ? new BrandId($value) : $value;
                        break;
                }

                $$name = $value;
            }

            return new PropertyInfo(
                propertyType: $propertyType,
                categoryId: $categoryId,
                brandId: $brandId,
                address: $address,
                postalCode: $postalCode,
                longitude: $longitude,
                latitude: $latitude,
                rooms: $rooms,
                zone: $zone,
                web: $web,
                destinationId: $destinationId,
                countryId: $countryId
            );
        };

        $builder
            ->add('propertyType', PropertyTypeType::class, [
                'label' => new TranslatableMessage('Tipo de propiedad'),
                'setter' => function (PropertyInfo &$info, PropertyType $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'propertyType' => $value
                    ]);
                },
                'required' => true
            ])
            ->add('categoryId', CategoryIdType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'categoryId' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('brandId', BrandIdType::class, [
                'label' => new TranslatableMessage('Marca'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'brandId' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('address', TextType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'address' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'postalCode' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('longitude', NumberType::class, [
                'label' => new TranslatableMessage('Longitud'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'longitude' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('latitude', NumberType::class, [
                'label' => new TranslatableMessage('Latitud'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'latitude' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('rooms', NumberType::class, [
                'label' => new TranslatableMessage('Habitaciones'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'rooms' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('web', TextType::class, [
                'label' => new TranslatableMessage('Web'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'web' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('zone', TextType::class, [
                'label' => new TranslatableMessage('Zone'),
                'setter' => function (PropertyInfo &$info, ?string $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'zone' => $value
                    ]);
                },
                'required' => false
            ])
            ->add('destinationId', DestinationType::class, [
                'label' => new TranslatableMessage('Destino'),
                'setter' => function (PropertyInfo &$info, ?Destination $value, FormInterface $form) use ($changePropertyInfo): void {
                    $info = $changePropertyInfo($info, [
                        'destinationId' => null !== $value ? $value->id() : null,
                        'countryId' => null !== $value ? $value->countryId() : null
                    ]);
                },
                'required' => true
            ])
        ;

        $repository = $this->destinationRepository;

        $builder->get('destinationId')
            ->addModelTransformer(new CallbackTransformer(
                function ($idToDestination) use ($repository): ?Destination {
                    if (null == $idToDestination) {
                        return null;
                    }

                    return $repository->find($idToDestination);
                },
                function ($destinationToId): ?Destination {
                    return $destinationToId;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PropertyInfo::class,
            'admin' => false
        ]);
    }
}