<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

trait CsrfTokenInSessionTrait
{
    private function csrfTokenInCurrentSession(string $tokenId): string
    {
        $session = $this->client->getContainer()->get('session.factory')->createSession();

        $cookie = $this->client->getCookieJar()->get($session->getName());
        if (null !== $cookie) {
            $session->setId($cookie->getValue());
        }

        $csrfToken = 'acceptance-csrf-token';
        $session->start();
        $session->set('_csrf/' . $tokenId, $csrfToken);
        $session->save();

        $this->client->setCookie($session->getName(), $session->getId());

        return $csrfToken;
    }
}
