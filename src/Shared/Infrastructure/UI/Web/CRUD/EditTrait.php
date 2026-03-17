<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

trait EditTrait
{
    /**
     * @param FormInterface<mixed> $form
     */
    public function edit(Request $request, FormInterface $form, \Closure $edit): bool
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $edit($form);
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar correctamente.'));
            }

            assert($form instanceof Form);

            if ($form->getClickedButton() && 'saveAndReturn' == $form->getClickedButton()->getName()) {
                return true;
            }
        }

        return false;
    }
}
