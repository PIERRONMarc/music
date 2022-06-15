<?php

namespace App\Service\Jwt;

use Lcobucci\JWT\Token\Plain;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Perform validation on a JWT token.
 */
class TokenValidator
{
    private TokenFactory $tokenFactory;

    public function __construct(TokenFactory $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * Validate that the Authorization header of a request is valid.
     *
     * @throws UnauthorizedHttpException
     */
    public function validateAuthorizationHeaderAndGetToken(?string $authorizationHeader): string
    {
        if (!$authorizationHeader) {
            throw new UnauthorizedHttpException('Bearer', 'JWT Token not found');
        }

        if (!str_starts_with($authorizationHeader, 'Bearer ') && !str_starts_with($authorizationHeader, 'bearer ')) {
            throw new UnauthorizedHttpException('Bearer', "The Authorization scheme named: 'Bearer' was not found");
        }

        $token = explode(' ', $authorizationHeader)[1];

        if (!$this->tokenFactory->validateToken($token)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid JWT Token');
        }

        return $token;
    }

    /**
     * Validate that the JWT token payload match the expected payload.
     *
     * @param mixed[] $expectedPayload
     *
     * @return mixed[]
     */
    public function validateAndGetPayload(string $token, array $expectedPayload): array
    {
        /** @var Plain $parsedToken */
        $parsedToken = $this->tokenFactory->parseToken($token);

        foreach ($expectedPayload as $claim) {
            if (!$parsedToken->claims()->get($claim)) {
                throw new AccessDeniedHttpException('Unexpected JWT token payload', null, 403);
            }
        }

        return $parsedToken->claims()->all();
    }
}
