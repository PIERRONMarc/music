<?php

namespace App\Service\Jwt;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TokenFactory
{
    private Configuration $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($_ENV['JWT_SECRET_KEY'])
        );
    }

    /**
     * @param mixed[] $options
     */
    public function createToken(array $options = []): Plain
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $builder = $this->config->builder();

        foreach ($options['claims'] as $name => $value) {
            $builder->withClaim($name, $value);
        }

        return $builder->getToken($this->config->signer(), $this->config->signingKey());
    }

    /**
     * Configure options given for the creation of a token.
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'claims' => [],
        ]);
    }

    public function validateToken(Token $token): bool
    {
        return $this
            ->config
            ->validator()
            ->validate($token, new SignedWith($this->config->signer(), $this->config->signingKey()))
        ;
    }
}
