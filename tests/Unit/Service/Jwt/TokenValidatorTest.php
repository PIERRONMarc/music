<?php

namespace App\Tests\Unit\Service\Jwt;

use App\Service\Jwt\TokenFactory;
use App\Service\Jwt\TokenValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenValidatorTest extends KernelTestCase
{
    /**
     * @dataProvider provideWrongAuthorization
     */
    public function testFailedValidateAuthorizationHeaderAndGetToken(?string $token, string $expectedTitle, bool $validateTokenResult = true): void
    {
        $this->expectExceptionMessage($expectedTitle);
        $this->expectException(UnauthorizedHttpException::class);

        $tokenFactory = $this->createMock(TokenFactory::class);
        $tokenFactory->method('validateToken')->willReturn($validateTokenResult);
        $tokenValidator = new TokenValidator($tokenFactory);
        $tokenValidator->validateAuthorizationHeaderAndGetToken($token);
    }

    private function provideWrongAuthorization(): \Generator
    {
        yield [
            'token' => null,
            'expectedTitle' => 'JWT Token not found',
        ];
        yield [
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            'expectedTitle' => "The Authorization scheme named: 'Bearer' was not found",
        ];
        yield [
            'jwt' => 'bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            'expectedTitle' => 'Invalid JWT Token',
            'validateTokenResult' => false,
        ];
    }

    public function testValidateAndGetAuthorizationHeader(): void
    {
        /** @var TokenFactory $tokenFactory */
        $tokenFactory = $this->getContainer()->get(TokenFactory::class);
        $token = $tokenFactory->createToken(['claims' => ['foo' => 'bar']])->toString();
        $tokenValidator = new TokenValidator($tokenFactory);

        $this->assertSame($token, $tokenValidator->validateAuthorizationHeaderAndGetToken('bearer '.$token));
    }

    public function testValidateAndGetPayload(): void
    {
        /** @var TokenFactory $tokenFactory */
        $tokenFactory = $this->getContainer()->get(TokenFactory::class);
        $token = $tokenFactory->createToken(['claims' => ['foo' => 'bar']]);

        $tokenValidator = new TokenValidator($tokenFactory);
        $this->assertSame(['foo' => 'bar'], $tokenValidator->validateAndGetPayload($token->toString(), ['foo']));

        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Unexpected JWT token payload');
        $this->expectException(AccessDeniedHttpException::class);
        $tokenValidator->validateAndGetPayload($token->toString(), ['foo', 'bar']);
    }
}
