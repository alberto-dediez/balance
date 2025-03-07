<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

trait EditTrait
{
    public function edit(Request $request, $form, \Closure $edit)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $edit($form);
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar correctamente.'));
            }

            if ($form->getClickedButton() && 'saveAndReturn' == $form->getClickedButton()->getName()) {
                return true;
            }
        }

        return false;
    }
}