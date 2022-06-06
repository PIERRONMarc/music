<?php

namespace App\Tests\Service\Jwt;

use App\Service\Jwt\TokenFactory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use PHPUnit\Framework\TestCase;

class TokenFactoryTest extends TestCase
{
    public const PRIVATE_KEY = __DIR__.'/../../config/jwt/private.pem';

    public function testTokenGeneration(): void
    {
        $tokenFactory = new TokenFactory(self::PRIVATE_KEY);
        $token = $tokenFactory->createToken();

        $this->assertInstanceOf(Plain::class, $token);
    }

    public function testClaims(): void
    {
        $tokenFactory = new TokenFactory(self::PRIVATE_KEY);
        $token = $tokenFactory->createToken([
            'claims' => ['roomId' => '7bddd678-9053-43b1-9620-747c52faa479'],
        ]);

        $this->assertSame('7bddd678-9053-43b1-9620-747c52faa479', $token->claims()->get('roomId'));
    }

    public function testTokenValidation(): void
    {
        $tokenFactory = new TokenFactory(self::PRIVATE_KEY);
        $validToken = $tokenFactory->createToken()->toString();
        $invalidToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        $this->assertTrue($tokenFactory->validateToken($validToken));
        $this->assertFalse($tokenFactory->validateToken($invalidToken));
    }

    public function testParsingToken(): void
    {
        $tokenFactory = new TokenFactory(self::PRIVATE_KEY);
        $token = $tokenFactory->createToken()->toString();
        $this->assertInstanceOf(Token::class, $tokenFactory->parseToken($token));
    }
}
