<?php

namespace App\Tests\Service\Jwt;

use App\Service\Jwt\TokenFactory;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use PHPUnit\Framework\TestCase;

class TokenFactoryTest extends TestCase
{
    public function testTokenGeneration(): void
    {
        $tokenFactory = new TokenFactory();
        $token = $tokenFactory->createToken();

        $this->assertInstanceOf(Plain::class, $token);
    }

    public function testClaims(): void
    {
        $tokenFactory = new TokenFactory();
        $token = $tokenFactory->createToken([
            'claims' => ['roomId' => '7bddd678-9053-43b1-9620-747c52faa479'],
        ]);

        $this->assertSame('7bddd678-9053-43b1-9620-747c52faa479', $token->claims()->get('roomId'));
    }

    public function testTokenValidation(): void
    {
        $tokenFactory = new TokenFactory();
        $validToken = $tokenFactory->createToken();
        $invalidToken = (new Parser(new JoseEncoder()))->parse('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

        $this->assertTrue($tokenFactory->validateToken($validToken));
        $this->assertFalse($tokenFactory->validateToken($invalidToken));
    }
}
