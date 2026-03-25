<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Bioscore;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\Clock\NativeClock;

class BioscoreJwtDecoder
{
    public function __construct(
        private readonly string $publicKeyPath
    ) {}

    /**
     * @return array<string, mixed>
     * @throws \Exception If the token is invalid
     */
    public function decode(string $tokenStr): array
    {
        $parser = new Parser(new JoseEncoder());
        
        try {
            $token = $parser->parse($tokenStr);
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse JWT token.', 0, $e);
        }

        $validator = new Validator();
        
        $keyContent = @file_get_contents($this->publicKeyPath);
        if (false === $keyContent) {
            $keyContent = $this->publicKeyPath;
        }

        try {
            $publicKey = InMemory::plainText($keyContent);
            $constraint = new SignedWith(new Sha256(), $publicKey);
            
            if (!$validator->validate($token, $constraint)) {
                throw new \Exception('Invalid JWT signature.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to validate JWT signature: ' . $e->getMessage(), 0, $e);
        }

        $clockConstraint = new LooseValidAt(new NativeClock(), new \DateInterval('PT30S')); // 30 seconds leeway
        if (!$validator->validate($token, $clockConstraint)) {
            throw new \Exception('Expired or future JWT token (clock skew).');
        }

        $payload = [];
        foreach ($token->claims()->all() as $name => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->getTimestamp();
            }
            $payload[$name] = $value;
        }

        return $payload;
    }
}
