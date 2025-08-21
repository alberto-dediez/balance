<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use App\Security\Domain\Model\User\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Application\CommandBus\CommandBusDispatcher;

trait BulkTrait
{
    public function bulkActions(Request $request, $bulkForm, User $user): bool
    {
        $bulkForm->handleRequest($request);

        if ($bulkForm->isSubmitted() && $bulkForm->isValid()) {
            $bulkRequest = $bulkForm->getData();

            $commands = $bulkRequest->commands();

            if (count($commands) == 0) {
                $this->addFlash('danger', new TranslatableMessage('Debes seleccionar algún elemento sobre el que poder realizar la acción'));

                return true;    
            }

            foreach ($commands as $command) {
                try {
                    CommandBusDispatcher::instance()
                        ->dispatch($command, $user)
                    ;

                    $this->addFlash('success', $bulkRequest->action->success($command));
                } catch (\Exception $e) {
                    $this->addFlash('danger', $bulkRequest->action->failure($command));
                }
            }

            return true;
        }

        return false;
    }
}