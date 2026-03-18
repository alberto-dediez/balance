<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Authorizer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;


class AuthorizerFactory
{
    /**
     * @param list<Authorizer> $authorizers
     */
    public function __construct(
        #[AutowireIterator('application.authorizer')]
        private readonly iterable $authorizers
    ) {

    }

    public function createFor(mixed $subject): ?Authorizer
    {
        if (count($this->authorizers) === 0) {
            return null;
        }

        foreach ($this->authorizers as $authorizer) {
            if ($authorizer->supports($subject)) {
                return $authorizer;
            }
        }

        return null;
    }
}
