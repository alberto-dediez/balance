<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\CRUD;

use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

final class FilterTraitHost
{
    use FilterTrait;

    public function __construct(private readonly FormFactoryInterface $formFactory) {}

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed>                   $options
     *
     * @return FormInterface<mixed>
     */
    public function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }
}
