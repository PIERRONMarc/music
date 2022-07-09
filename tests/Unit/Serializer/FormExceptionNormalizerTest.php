<?php

namespace App\Tests\Unit\Serializer;

use App\Exception\FormHttpException;
use App\Serializer\FormExceptionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormExceptionNormalizerTest extends TestCase
{
    public function testSupportNormalization(): void
    {
        $formException = $this->createMock(FormHttpException::class);
        $formExceptionNormalizer = new FormExceptionNormalizer();

        $this->assertTrue($formExceptionNormalizer->supportsNormalization($formException));
        $this->assertFalse($formExceptionNormalizer->supportsNormalization(new AccessDeniedHttpException()));
    }

    public function testNormalization(): void
    {
        $formExceptionNormalizer = new FormExceptionNormalizer();
        $violations = $formExceptionNormalizer->normalize($this->getFormException());

        $this->assertSame('foo', $violations[0]['property']);
        $this->assertSame('Invalid foo data.', $violations[0]['message']);
    }

    private function getFormException(): FormHttpException
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('foo');
        $formError = $this->createMock(FormError::class);
        $formError->method('getOrigin')->willReturn($form);
        $formError->method('getMessage')->willReturn('Invalid foo data.');
        $formErrorIterrator = new FormErrorIterator($form, [$formError]);
        $form->method('getErrors')->willReturn($formErrorIterrator);

        return new FormHttpException($form);
    }
}
